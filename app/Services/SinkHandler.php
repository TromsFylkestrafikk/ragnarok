<?php

namespace App\Services;

use App\Models\Sink;
use App\Models\Chunk;
use App\Helpers\Utils;
use Closure;
use Exception;
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
     * @var ChunkDispatcher|null
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
        return $this->getChunkDispatcher()->import($this->getNewChunks()->pluck('id')->toArray());
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
        $this->doRunOperation(fn() => $this->src->fetch($chunk->chunk_id), $chunk, 'fetch');
        $this->info('Fetched chunk %s in %.2f seconds', $chunk->chunk_id, microtime(true) - $start);
        return $this;
    }

    /**
     * @param Chunk $chunk
     * @return $this
     */
    public function removeChunk($chunk): SinkHandler
    {
        $this->doRunOperation(fn() => $this->src->removeChunk($chunk->chunk_id), $chunk, 'fetch', 'new');
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
        $this->doRunOperation(fn() => $this->src->import($chunk->chunk_id), $chunk, 'import');
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
        $this->doRunOperation(fn() => $this->src->deleteImport($chunk->chunk_id), $chunk, 'import', 'new');
        $this->info('Deleted chunk \'%s\' from DB in %.2f seconds', $chunk->chunk_id, microtime(true) - $start);
        return $this;
    }

    /**
     * Perform given operation and update chunk status.
     *
     * @param Closure $run
     * @param Chunk $chunk
     * @param string $stage 'fetch' or 'import'.
     * @param string $finalState Operation state if successful.
     *
     * @return mixed
     */
    protected function doRunOperation(Closure $run, Chunk $chunk, $stage, $finalState = 'finished')
    {
        $chunk->{$stage . '_status'} = 'in_progress';
        $chunk->{$stage . '_message'} = null;
        $chunk->{$stage . '_size'} = null;
        $chunk->{$stage . '_version'} = null;
        $chunk->{$stage . 'ed_at'} = null;
        $chunk->save();
        try {
            $result = $run();
        } catch (Exception $except) {
            $this->error("Got exception in %s stage where final state should be %s", $stage, $finalState);
            $chunk->{$stage . '_status'} = 'failed';
            $chunk->{$stage . '_message'} = Utils::exceptToStr($except);
            $chunk->{$stage . '_batch'} = null;
            $chunk->save();
            throw $except;
        }
        $chunk->{$stage . '_status'} = $finalState;
        $chunk->{$stage . '_batch'} = null;
        if ($finalState === 'finished') {
            if ($stage === 'fetch') {
                $chunk->chunk_date = $this->src->getChunkDate($chunk->chunk_id);
            }
            $chunk->{$stage . '_size'} = $result;
            $chunk->{$stage . '_version'} = $stage === 'fetch'
                ? $this->src->getChunkVersion($chunk->chunk_id)
                : $chunk->fetch_version;
            $chunk->{$stage . 'ed_at'} = now();
        }
        $chunk->save();
        return $result;
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
