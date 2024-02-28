<?php

namespace App\Services;

use App\Jobs\BroadcastsBatch;
use App\Jobs\DeleteFetchedChunk;
use App\Jobs\DeleteImportedChunk;
use App\Jobs\FetchChunk;
use App\Jobs\ImportChunk;
use App\Models\BatchSink;
use App\Models\Chunk;
use App\Models\Sink;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Ragnarok\Sink\Traits\LogPrintf;

/**
 * Operations on chunks as batches.
 *
 * Dispatch service around batch jobs for chunk operations that (may) spend a
 * long time per operation.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ChunkDispatcher
{
    use BroadcastsBatch;
    use LogPrintf;

    /**
     * @var bool
     */
    protected $forceImport = false;

    /**
     * @var bool
     */
    protected $forceFetch = false;

    /**
     * @var mixed[]
     */
    protected $chunkBatchMap = [];

    public function __construct(protected Sink $sink)
    {
        $this->logPrintfInit("[ChunkDispatcher %s]: ", $sink->id);
    }

    /**
     * Force chunks to be fetched, even if they exists locally.
     *
     * @param bool $force
     *
     * @return $this
     */
    public function setForceFetch($force = true): ChunkDispatcher
    {
        if ($force) {
            $this->debug("Forcing fetch ...");
        }
        $this->forceFetch = $force;
        return $this;
    }

    /**
     * Force chunks to be imported, even if they successfully already are.
     *
     * @param bool $force
     *
     * @return $this
     */
    public function setForceImport($force = true): ChunkDispatcher
    {
        if ($force) {
            $this->debug("Forcing import ...");
        }
        $this->forceImport = $force;
        return $this;
    }

    /**
     * Fetch numerous chunks in bulk through batched jobs.
     *
     * @param int[] $ids
     *
     * @return string|null
     */
    public function fetch($ids): string|null
    {
        if ($this->sink->is_live) {
            return null;
        }
        return $this->dispatchJobs($this->makeBatchJobs(FetchChunk::class, $ids), __FUNCTION__);
    }

    /**
     * Remove chunks from stage 1 storage.
     *
     * @param int[] $ids
     *
     * @return string|null
     */
    public function deleteFetched($ids): string|null
    {
        return $this->dispatchJobs($this->makeBatchJobs(DeleteFetchedChunk::class, $ids), __FUNCTION__);
    }

    /**
     * Import chunks from stage1 to DB.
     *
     * @param int[] $ids
     *
     * @return string|null
     */
    public function import($ids): string|null
    {
        return $this->dispatchJobs($this->makeImportBatchJobs($ids), __FUNCTION__);
    }

    /**
     * Delete imported data from these chunks.
     *
     * @param array $ids List of chunk model IDs.
     *
     * @return string|null
     */
    public function deleteImported($ids): string|null
    {
        return $this->dispatchJobs($this->makeBatchJobs(DeleteImportedChunk::class, $ids), __FUNCTION__);
    }

    /**
     * Create Batch job for these chunks.
     *
     * @param string $jobClass Job to work on chunks
     * @param int[] $ids Chunk model IDs.
     *
     * @return array
     */
    protected function makeBatchJobs($jobClass, $ids): array
    {
        $this->chunkBatchMap = [];
        $jobs = [];
        $models = $this->getChunkModels($ids, $jobClass);
        foreach ($models as $chunk) {
            /** @var Chunk $chunk  */
            $jobs[] = new $jobClass($chunk->id);
            // Collect the chunks that should attach batch ID to them.
            $this->addPendingChunk($chunk->id, $jobClass);
        };
        return $jobs;
    }

    /**
     * Add import jobs ready for batched operation.
     *
     * Where chunks aren't yet fetched, chain the fetch job before the actual
     * import job.
     *
     * @param int[] $ids
     *
     * @return mixed[]
     */
    protected function makeImportBatchJobs($ids): array
    {
        $this->chunkBatchMap = [];
        $models = $this->getChunkModels($ids, ImportChunk::class);
        return $models->reduce(function (?array $jobs, Model $chunk) {
            /** @var Chunk $chunk */
            $needFetch = $this->forceFetch || $chunk->fetch_status !== 'finished';
            if ($needFetch && $this->sink->status !== 'live') {
                return $jobs;
            }
            $jobs[] = $needFetch ? [new FetchChunk($chunk->id), new ImportChunk($chunk->id)] : new ImportChunk($chunk->id);
            if ($needFetch) {
                $this->addPendingChunk($chunk->id, FetchChunk::class);
            }
            $this->addPendingChunk($chunk->id, ImportChunk::class);
            return $jobs;
        }, []);
    }

    /**
     * Run jobs in batch and decorate with logging and error handling.
     *
     * @param mixed[] $jobs
     * @param string $name
     *
     * @return null|string
     */
    protected function dispatchJobs(array $jobs, string $name): null|string
    {
        if (!count($jobs)) {
            $this->notice('No jobs to dispatch');
            return null;
        }
        $start = microtime(true);
        // The batch is serialized. No use of $this in callbacks.
        $sinkId = $this->sink->id;
        $batch = Bus::batch($jobs)->name("{$sinkId}: $name")->then(function (Batch $batch) use ($start) {
            Log::info(sprintf(
                '[%s]: Processed %d of %d jobs in %.2f seconds. Canceled: %s, Failed: %d. Batch ID: %s',
                $batch->name,
                $batch->processedJobs(),
                $batch->totalJobs,
                microtime(true) - $start,
                $batch->canceled() ? 'YES' : 'no',
                $batch->failedJobs,
                $batch->id
            ));
        })->finally(function (Batch $batch) use ($sinkId) {
            static::resetChunksBatch($batch);
            if ($batch->pendingJobs && $batch->failedJobs && !$batch->cancelled()) {
                Log::notice(sprintf("[%s]: Batch run is complete with failures. Cancelling ...", $batch->name));
                $batch->cancel();
                $batch = Bus::findBatch($batch->id);
            }
            BroadcastsBatch::broadcast($sinkId, $batch);
        })->allowFailures()->onQueue('data')->dispatch();
        $this->updateChunksBatch($batch);
        BroadcastsBatch::broadcast($sinkId, $batch);
        $this->info("[%s]: Initiated processing of %d jobs. Batch ID: %s", $batch->name, $batch->totalJobs, $batch->id);
        return $batch->id;
    }

    /**
     * Get the models for given chunk IDs.
     *
     * @param int[] $chunkIds The model ID of chunks.
     *
     * @return Collection<Chunk>
     */
    protected function getChunkModels(array $chunkIds, string $jobClass): Collection
    {
        $query = Chunk::where('sink_id', $this->sink->id)->whereIn('id', $chunkIds);
        $scope = [
            FetchChunk::class => $this->forceFetch ? 'canFetch' : 'needFetch',
            ImportChunk::class => $this->forceImport ? 'canImport' : 'needImport',
            DeleteFetchedChunk::class => 'canDeleteFetched',
            DeleteImportedChunk::class => 'canDeleteImported',
        ][$jobClass];
        return $query->$scope()->get();
    }

    protected function addPendingChunk(int $chunkId, string $jobClass): void
    {
        $batchColumn = [
            FetchChunk::class => 'fetch_batch',
            ImportChunk::class => 'import_batch',
            DeleteFetchedChunk::class => 'fetch_batch',
            DeleteImportedChunk::class => 'import_batch',
        ][$jobClass];
        $this->chunkBatchMap[$batchColumn][] = $chunkId;
    }

    /**
     * Add batch info to chunks for current batch.
     *
     * Also, add map between batch and sink. The chunks added are collected
     * during chunk job selection at an earlier stage.
     */
    protected function updateChunksBatch(Batch $batch): void
    {
        /** @var BatchSink $bSink */
        $bSink = BatchSink::firstOrNew(['batch_id' => $batch->id]);
        $bSink->sink_id = $this->sink->id;
        $bSink->save();
        foreach (['fetch_batch', 'import_batch'] as $column) {
            if (!empty($this->chunkBatchMap[$column])) {
                Chunk::whereIn('id', $this->chunkBatchMap[$column])->update([$column => $batch->id]);
            }
        }
    }

    /**
     * Reset batch info on chunks and its sink mapping.
     */
    protected static function resetChunksBatch(Batch $batch): void
    {
        $bSink = BatchSink::firstWhere('batch_id', $batch->id);
        if ($bSink) {
            $bSink->delete();
        }
        Chunk::whereFetchBatch($batch->id)->orWhere('import_batch', $batch->id)->update([
            'fetch_batch' => null,
            'import_batch' => null,
        ]);
    }
}
