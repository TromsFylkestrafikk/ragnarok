<?php

namespace App\Services;

use App\Jobs\BroadcastsBatch;
use App\Jobs\DeleteFetchedChunk;
use App\Jobs\DeleteImportedChunk;
use App\Jobs\FetchChunk;
use App\Jobs\ImportChunk;
use App\Models\Chunk;
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
    use LogPrintf;

    protected $forceImport = false;
    protected $forceFetch = false;

    public function __construct(protected string $sinkId)
    {
        $this->logPrintfInit("[ChunkDispatcher %s]: ", $sinkId);
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
        $jobs = [];
        $models = $this->getChunkModels($ids, $jobClass);
        foreach ($models as $chunk) {
            /** @var Chunk $chunk  */
            $jobs[] = new $jobClass($chunk->id);
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
        $models = $this->getChunkModels($ids, ImportChunk::class);
        return $models->reduce(function (?array $jobs, Model $chunk) {
            /** @var Chunk $chunk */
            $jobs[] = ($this->forceFetch || $chunk->fetch_status !== 'finished')
                ? [new FetchChunk($chunk->id), new ImportChunk($chunk->id)]
                : new ImportChunk($chunk->id);
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
        $sinkId = $this->sinkId;
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
            if ($batch->pendingJobs && $batch->failedJobs && !$batch->cancelled()) {
                Log::notice(sprintf("[%s]: Batch run is complete with failures. Cancelling ...", $batch->name));
                $batch->cancel();
                $batch = Bus::findBatch($batch->id);
            }
            BroadcastsBatch::broadcast($sinkId, $batch);
        })->allowFailures()->onQueue('data')->dispatch();
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
        $query = Chunk::where('sink_id', $this->sinkId)->whereIn('id', $chunkIds);
        $scope = [
            FetchChunk::class => $this->forceFetch ? 'canFetch' : 'needFetch',
            ImportChunk::class => $this->forceImport ? 'canImport' : 'needImport',
            DeleteFetchedChunk::class => 'canDeleteFetched',
            DeleteImportedChunk::class => 'canDeleteImported',
        ][$jobClass];
        $chunks = $query->$scope()->get();
        return $chunks;
    }
}
