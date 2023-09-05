<?php

namespace App\Services;

use App\Models\SinkImport;
use App\Models\Chunk;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use TromsFylkestrafikk\RagnarokSink\Sinks\SinkBase;
use TromsFylkestrafikk\RagnarokSink\Services\DbBulkInsert;
use TromsFylkestrafikk\RagnarokSink\Traits\LogPrintf;

class RagnarokSink
{
    use LogPrintf;

    /**
     * Cache available chunks for this many seconds.
     */
    const CHUNK_CACHE_EXPIRE = 60 * 60;

    public function __construct(public SinkBase $src)
    {
        $this->logPrintfInit("[Sink %s]: ", $src->id);
    }

    public function lastImport()
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

    public function getChunkStatus(string $sinkId, array $chunkIds): Collection
    {
        return Chunk::where('sink_id', $sinkId)
            ->whereIn('id', $chunkIds)
            ->get()
            ->keyBy('id');
    }

    /**
     * @param int $itemsPerPage
     *
     * @return array
     */
    public function getChunks($itemsPerPage = 20, $orderBy = null)
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
     * Stage one retrieval of data from sink.
     *
     * @param array $chunkIds List of chunks to fetch
     *
     * @return $this
     */
    public function fetchChunks($chunkIds): RagnarokSink
    {
        $chunks = $this->getChunkModels($chunkIds);
        $start = microtime(true);
        $this->debug('Fetching %d chunks ...', $chunks->count());
        foreach ($chunks as $chunk) {
            /** @var Chunk $chunk */
            $this->fetchChunk($chunk);
        }
        $this->info("Fetched %d chunks in %.2f seconds", count($chunkIds), microtime(true) - $start);
        return $this;
    }

    /**
     * Remove chunks from stage 1 storage.
     *
     * @param array $chunkIds
     *
     * @return $this
     */
    public function removeChunks($chunkIds): RagnarokSink
    {
        $chunks = Chunk::where('sink_id', $this->src->id)->whereIn('id', $chunkIds)->get();
        $start = microtime(true);
        foreach ($chunks as $chunk) {
            /** @var Chunk $chunk */
            $chunk->fetched_at = null;
            $chunk->fetch_status = 'new';
            $chunk->save();
            $this->src->removeChunk($chunk->chunk_id);
        }
        $this->info('Deleted %d chunks in %.2f seconds', count($chunkIds), microtime(true) - $start);
        return $this;
    }

    /**
     * @param array $chunkIds
     */
    public function importChunks($chunkIds): RagnarokSink
    {
        $chunks = $this->getChunkModels($chunkIds);
        $start = microtime(true);
        $this->debug('Importing %d chunks ...', $chunks->count());
        foreach ($chunks as $chunk) {
            /** @var Chunk $chunk */
            $this->importChunk($chunk);
        }
        $this->info("Imported %d chunks in %.2f seconds", count($chunkIds), microtime(true) - $start);
        return $this;
    }

    /**
     * Delete imported data from these chunk IDs.
     *
     * @param array $chunkIds
     *
     * @return $this
     */
    public function deleteImports($chunkIds): RagnarokSink
    {
        $chunks = Chunk::where('sink_id', $this->src->id)->whereIn('id', $chunkIds)->get();
        $this->debug('Deleting import of %d chunks ...', $chunks->count());
        $start = microtime(true);
        foreach ($chunks as $chunk) {
            /** @var Chunk $chunk */
            $chunk->imported_at = null;
            $chunk->import_status = 'new';
            $chunk->save();
            $this->src->deleteImport($chunk->chunk_id);
        }
        $this->info('Deleted %d chunks from DB in %.2f seconds', $chunks->count(), microtime(true) - $start);
        return $this;
    }

    /**
     * @param Chunk $chunk
     *
     * @return $this
     */
    protected function fetchChunk($chunk)
    {
        $start = microtime(true);
        $chunk->fetch_status = 'in_progress';
        $chunk->fetched_at = null;
        $chunk->save();
        $chunk->fetch_status = $this->src->fetch($chunk->chunk_id) ? 'finished' : 'failed';
        $chunk->fetched_at = now();
        $chunk->save();
        $this->debug('Fetched chunk %s in %.2f seconds', $chunk->chunk_id, microtime(true) - $start);
        return $this;
    }

    /**
     * @param Chunk $chunk
     *
     * @return $this
     */
    protected function importChunk($chunk)
    {
        if ($chunk->fetch_status !== 'finished') {
            if ($chunk->fetch_status === 'new') {
                $this->fetchChunk($chunk);
            } else {
                throw new Exception('Cannot import. Fetch is in progress');
            }
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
        $this->debug('Imported chunk %s in %.2f seconds', $chunk->chunk_id, microtime(true) - $start);
        return $this;
    }

    /**
     * @param array $chunkIds
     * @return Collection
     */
    protected function getChunkModels($chunkIds)
    {
        $chunks = Chunk::where('sink_id', $this->src->id)->whereIn('id', $chunkIds)->get();
        if ($chunks->count() !== count($chunkIds)) {
            throw new Exception('Mismatch between requested and actual chunks');
        }
        return $chunks;
    }

    /**
     * Create missing chunks in db.
     *
     * @return void
     */
    protected function initChunks()
    {
        if (Cache::get($this->initCacheKey())) {
            return;
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
    }

    /**
     * Get list of Chunk IDs not present in storage.
     *
     * @return array
     */
    protected function chunkIdsNotInDb()
    {
        $ids = $this->src->getChunkIds();
        $existing = Chunk::select(['chunk_id'])
            ->where('sink_id', $this->src->id)
            ->orderBy('chunk_id')
            ->pluck('chunk_id')
            ->toArray();
        return array_diff($ids, $existing);
    }

    protected function initCacheKey()
    {
        return sprintf('ragnarok-sink-%d-chunk-initialized', $this->src->id);
    }
}
