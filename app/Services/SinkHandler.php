<?php

namespace App\Services;

use App\Models\Sink;
use App\Models\Chunk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Ragnarok\Sink\Sinks\SinkBase;
use Ragnarok\Sink\Traits\LogPrintf;

/**
 * Wrapper around sink model and is associated SinkBase implementation.
 */
class SinkHandler
{
    use LogPrintf;

    /**
     * @var SinkBase
     */
    public $src;

    /**
     * @var ChunkDispatcher
     */
    protected $dispatcher = null;

    /**
     * Cache available chunks for this many seconds.
     */
    public const CHUNK_CACHE_EXPIRE = 60 * 60;

    public function __construct(public Sink $sink)
    {
        $this->src = new ($sink->impl_class)();
        $this->logPrintfInit("[Sink %s]: ", $this->sink->id);
    }

    /**
     * Import newest chunks.
     *
     * @return string|null
     */
    public function importNewChunks(): string|null
    {
        return $this->getChunkDispatcher()->importChunks($this->getNewChunks()->pluck('id')->toArray());
    }

    /**
     * Get the newest un-imported chunks.
     *
     * This will get the newest chunks that is yet un-imported.  Searching is
     * terminated when a non-new chunk is found.
     *
     * @param int $scanCount Limit of chunks to scan.
     *
     * @return Collection
     */
    public function getNewChunks($scanCount = 20): Collection
    {
        $stillNew = true;
        return $this->getChunksBuilder()->take($scanCount)->get()->filter(function ($chunk) use (&$stillNew) {
            /** @var Chunk $chunk */
            $stillNew = $stillNew && $chunk->import_status === 'new';
            return $stillNew;
        });
    }

    /**
     * Get the base chunk model builder for this sink.
     *
     * @return Builder
     */
    public function getChunksBuilder(): Builder
    {
        $this->initChunks();
        return Chunk::where('sink_id', $this->sink->id)->orderBy('chunk_id', 'desc');
    }

    /**
     * Get the sink's service for dispatching data jobs.
     *
     * @return ChunkDispatcher
     */
    public function getChunkDispatcher(): ChunkDispatcher
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = new ChunkDispatcher($this->sink->id);
        }
        return $this->dispatcher;
    }

    /**
     * @param Chunk $chunk
     *
     * @return $this
     */
    public function fetchChunk($chunk): SinkHandler
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
    public function removeChunk($chunk): SinkHandler
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
    public function importChunk($chunk): SinkHandler
    {
        $this->debug("Importing chunk '%s' ...", $chunk->chunk_id);
        if ($chunk->fetch_status !== 'finished') {
            $this->error('Chunk not properly fetched from source yet.');
            return $this;
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
    public function deleteImport(Chunk $chunk): SinkHandler
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
    protected function initChunks(): SinkHandler
    {
        if (Cache::get($this->initCacheKey())) {
            return $this;
        }
        $newIds = $this->chunkIdsNotInDb();
        $records = [];
        foreach ($newIds as $chunkId) {
            $records[] = [
                'chunk_id' => $chunkId,
                'sink_id' => $this->sink->id,
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
            ->where('sink_id', $this->sink->id)
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
        return sprintf('ragnarok-sink-%s-chunk-initialized', $this->sink->id);
    }
}
