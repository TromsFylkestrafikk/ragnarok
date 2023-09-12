<?php

namespace App\Services;

use App\Models\SinkImport;
use App\Models\Chunk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use TromsFylkestrafikk\RagnarokSink\Sinks\SinkBase;
use TromsFylkestrafikk\RagnarokSink\Traits\LogPrintf;

class RagnarokSink
{
    use LogPrintf;

    /**
     * Cache available chunks for this many seconds.
     */
    public const CHUNK_CACHE_EXPIRE = 60 * 60;

    public function __construct(public SinkBase $src)
    {
        $this->logPrintfInit("[Sink %s]: ", $src->id);
    }

    public function lastImport(): SinkImport|null
    {
        return SinkImport::where('sink_id', $this->src->id)->orderBy('started_at', 'desc')->first();
    }

    /**
     * Get the state of a single sink
     *
     * @return mixed[]
     */
    public function getState(): array
    {
        return [
            'id' => $this->src->id,
            'title' => $this->src->title,
            'fromDate' => $this->src->getFromDate(),
            'toDate' => $this->src->getToDate(),
            'chunksCount' => $this->src->chunksCount(),
        ];
    }

    /**
     * @param int $itemsPerPage
     * @param null|array $orderBy
     *
     * @return array
     */
    public function getChunks($itemsPerPage = 20, $orderBy = null): array
    {
        $this->initChunks();
        /** @var Builder $baseQuery */
        $baseQuery = Chunk::where('sink_id', $this->src->id);
        if (!empty($orderBy)) {
            $baseQuery->orderBy($orderBy['key'], $orderBy['order']);
        }
        return $baseQuery->orderBy('chunk_id', 'desc')->paginate($itemsPerPage)->items();
    }

    /**
     * @param Chunk $chunk
     *
     * @return $this
     */
    public function fetchChunk($chunk): RagnarokSink
    {
        $this->debug("Fetching chunk '%s' ...", $chunk->chunk_id);
        $start = microtime(true);
        $chunk->fetch_status = 'in_progress';
        $chunk->fetched_at = null;
        $chunk->save();
        $chunk->fetch_status = $this->src->fetch($chunk->chunk_id) ? 'finished' : 'failed';
        $chunk->fetched_at = now();
        $chunk->save();
        $this->info('Fetched chunk %s in %.2f seconds', $chunk->chunk_id, microtime(true) - $start);
        return $this;
    }

    /**
     * @param Chunk $chunk
     * @return $this
     */
    public function removeChunk($chunk): RagnarokSink
    {
        $chunk->fetched_at = null;
        $chunk->fetch_status = 'new';
        $chunk->save();
        $this->src->removeChunk($chunk->chunk_id);
        $this->info("Removed retrieved stage 1 data for chunk '%s'", $chunk->chunk_id);
        return $this;
    }

    /**
     * @param Chunk $chunk
     *
     * @return $this
     */
    public function importChunk($chunk): RagnarokSink
    {
        $this->debug("Importing chunk '%s' ...", $chunk->chunk_id);
        if ($chunk->fetch_status === 'in_progress') {
            $this->error("Cannot import chunk '%s'. Fetch is in progress.", $chunk->chunk_id);
            return $this;
        }
        if ($chunk->fetch_status !== 'finished') {
            $this->fetchChunk($chunk);
        }
        if ($chunk->import_status === 'in_progress') {
            $this->error("Cannot import chunk '%s'. Import already in progress", $chunk->chunk_id);
            return $this;
        }
        $start = microtime(true);
        $chunk->import_status = 'in_progress';
        $chunk->imported_at = null;
        $chunk->save();
        // Delete existing data.
        $this->src->deleteImport($chunk->chunk_id);
        $chunk->import_status = $this->src->import($chunk->chunk_id) ? 'finished' : 'failed';
        $chunk->imported_at = now();
        $chunk->save();
        $this->info("Imported chunk '%s' in %.2f seconds", $chunk->chunk_id, microtime(true) - $start);
        return $this;
    }

    /**
     * Delete imported data associated with chunk.
     *
     * @param Chunk $chunk
     *
     * @return $this
     */
    public function deleteImport(Chunk $chunk): RagnarokSink
    {
        $start = microtime(true);
        $chunk->imported_at = null;
        $chunk->import_status = 'in_progress';
        $chunk->save();
        $this->src->deleteImport($chunk->chunk_id);
        $chunk->import_status = 'new';
        $chunk->save();
        $this->info('Deleted chunk \'%s\' from DB in %.2f seconds', $chunk->chunk_id, microtime(true) - $start);
        return $this;
    }

    /**
     * Create missing chunks in db.
     *
     * @return $this
     */
    protected function initChunks(): RagnarokSink
    {
        if (Cache::get($this->initCacheKey())) {
            return $this;
        }
        $newIds = $this->chunkIdsNotInDb();
        $records = [];
        foreach ($newIds as $chunkId) {
            $records[] = [
                'chunk_id' => $chunkId,
                'sink_id' => $this->src->id,
            ];
        }
        if (count($records)) {
            Chunk::insertOrIgnore($records);
        }
        Cache::put($this->initCacheKey(), true, self::CHUNK_CACHE_EXPIRE);
        return $this;
    }

    /**
     * Get list of Chunk IDs not present in storage.
     *
     * @return array
     */
    protected function chunkIdsNotInDb(): array
    {
        $ids = $this->src->getChunkIds();
        $existing = Chunk::select(['chunk_id'])
            ->where('sink_id', $this->src->id)
            ->orderBy('chunk_id')
            ->pluck('chunk_id')
            ->toArray();
        return array_diff($ids, $existing);
    }

    /**
     * @return string
     */
    protected function initCacheKey(): string
    {
        return sprintf('ragnarok-sink-%d-chunk-initialized', $this->src->id);
    }
}
