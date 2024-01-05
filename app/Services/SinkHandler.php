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
use Ragnarok\Sink\Models\SinkFile;
use Ragnarok\Sink\Services\LocalFile;
use Ragnarok\Sink\Sinks\SinkBase;

/**
 * Wrapper around sink model and is associated SinkBase implementation.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SinkHandler
{
    use \Ragnarok\Sink\Traits\LogPrintf;

    /**
     * @var SinkBase
     */
    public $src;

    /**
     * @var ChunkDispatcher|null
     */
    protected $dispatcher = null;

    /**
     * @var float
     */
    protected $operationRunTime = 0;

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
        $this->debug("Chunk %s: Fetching ...", $chunk->chunk_id);
        $this->doRunOperation(function () use ($chunk) {
            /** @var SinkFile $file */
            $file = $this->src->fetch($chunk->chunk_id);
            $chunk->sink_file_id = $file->id;
            $chunk->chunk_date = $this->src->getChunkDate($chunk->chunk_id);
            $chunk->fetch_size = $file->size;
            $chunk->fetch_version = $file->checksum;
        }, $chunk, 'fetch');
        $this->info('Chunk %s: Fetched in %.2f seconds', $chunk->chunk_id, $this->operationRunTime);
        return $this;
    }

    /**
     * @param Chunk $chunk
     * @return $this
     */
    public function removeChunk($chunk): SinkHandler
    {
        $this->debug('Chunk %s: Removing ...', $chunk->chunk_id);
        $this->doRunOperation(function () use ($chunk) {
            unlink($this->getChunkFilepath($chunk));
            $chunk->sinkFile->delete();
            $chunk->fetch_size = null;
            $chunk->sink_file_id = null;
        }, $chunk, 'fetch', 'new');
        $this->info("Chunk %s: Removed retrieved stage 1 data in %.2f seconds", $chunk->chunk_id, $this->operationRunTime);
        return $this;
    }

    /**
     * @param Chunk $chunk
     *
     * @return $this
     */
    public function importChunk($chunk): SinkHandler
    {
        $this->debug("Chunk %s: Importing ...", $chunk->chunk_id);
        if ($chunk->fetch_status !== 'finished') {
            $this->error('Chunk not properly fetched from source yet.');
            return $this;
        }
        if ($chunk->import_status === 'in_progress') {
            $this->error("Cannot import chunk '%s'. Import already in progress", $chunk->chunk_id);
            return $this;
        }
        $this->doRunOperation(function () use ($chunk) {
            $chunk->import_size = $this->src->import($chunk->chunk_id, $chunk->sinkFile);
            $chunk->import_version = $chunk->fetch_version;
        }, $chunk, 'import');
        $this->info("Chunk %s: Imported %d entries in  %.2f seconds", $chunk->chunk_id, $chunk->import_size, $this->operationRunTime);
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
        $this->debug("Chunk %s: Deleting import ...", $chunk->chunk_id);
        $this->doRunOperation(fn() => $this->src->deleteImport($chunk->chunk_id, $chunk->sinkFile), $chunk, 'import', 'new');
        $this->info('Chunk %s: Deleted from DB in %.2f seconds', $chunk->chunk_id, $this->operationRunTime);
        return $this;
    }

    /**
     * @return string|null Path to single file download for chunk
     */
    public function getChunkFilepath(Chunk $chunk): string|null
    {
        return (new LocalFile($chunk->sink_id, $chunk->sinkFile))->getPath();
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
        $start = microtime(true);
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
            $chunk->{$stage . 'ed_at'} = now();
        }
        $chunk->save();
        $this->operationRunTime = microtime(true) - $start;
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
