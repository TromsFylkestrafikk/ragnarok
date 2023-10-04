<?php

namespace App\Services;

use App\Events\ImportsFinished;
use App\Jobs\DeleteImportedChunk;
use App\Jobs\FetchChunk;
use App\Jobs\ImportChunk;
use App\Jobs\RemoveChunk;
use App\Models\Chunk;
use Closure;
use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Ragnarok\Sink\Traits\LogPrintf;
use Throwable;

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
        $chunkCount = count($ids);
        $jobs = $this->makeBatchJobs(FetchChunk::class, $ids, $this->forceFetch
            ? null
            : fn(Chunk $chunk) => $chunk->fetch_status !== 'finished');
        if (!count($jobs)) {
            $this->notice('No chunks to fetch');
            return null;
        }
        // The batch is serialized. No use of $this in callbacks.
        $batch = Bus::batch($jobs)->name('Fetch chunks')->then(function (Batch $batch) use ($chunkCount) {
            Log::info(sprintf('[%s]: Successfully retrieved %d chunks from sink. (Batch %s)', $batch->name, $chunkCount, $batch->id));
        })->catch(function (Batch $batch, Throwable $except) {
            Log::error(sprintf('[%s]: On Batch %s: %s', $batch->name, $batch->id, $except->getMessage()));
        })->allowFailures()->onQueue('data')->dispatch();
        $this->info("[%s]: Initiated batch fetching of %d chunks from sink .. (Batch %s).", $batch->name, $chunkCount, $batch->id);
        return $batch->id;
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
        $start = microtime(true);
        $jobs = $this->makeBatchJobs(RemoveChunk::class, $ids);
        if (!$jobs) {
            $this->notice("Found no chunks to delete");
            return null;
        }
        $batch = Bus::batch($jobs)->name('Delete chunks')->then(function (Batch $batch) use ($start) {
            Log::info(sprintf(
                '[%s]: Deleted %d chunks in %.2f seconds. Batch ID: %s',
                $batch->name,
                $batch->totalJobs,
                microtime(true) - $start,
                $batch->id
            ));
        })->catch(function (Batch $batch, Throwable $except) {
            Log::error(sprintf("[%s]: Batch %s: %s", $batch->name, $batch->id, $except->getMessage()));
        })->allowFailures()->onQueue('data')->dispatch();
        $this->info(
            "[%s]: Initiated batch deleting %d chunks of retrieved data. Batch ID: %s",
            $batch->name,
            $batch->totalJobs,
            $batch->id
        );
        return $batch->id;
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
        $chunkCount = count($ids);
        $jobs = $this->makeImportBatchJobs($ids);
        $start = microtime(true);
        if (!count($jobs)) {
            $this->notice('No chunks to import');
            return null;
        }
        $batch = Bus::batch($jobs)->name('Import chunks')->then(function (Batch $batch) use ($start) {
            Log::info(sprintf(
                "[%s]: %d fetch + import jobs successfully executed in %.2f seconds. Batch ID: %s",
                $batch->name,
                $batch->totalJobs,
                microtime(true) - $start,
                $batch->id
            ));
        })->catch(function (Batch $batch, Throwable $except) {
            Log::error(sprintf('[%s]: On Batch %s: %s', $batch->name, $batch->id, $except->getMessage()));
        })->finally(function (Batch $batch) {
            ImportsFinished::dispatch($this->sinkId, $batch->id, $batch->totalJobs, $batch->failedJobs);
        })->allowFailures()->onQueue('data')->dispatch();
        $this->info("[%s]: Batch initiated on %d chunks with batch ID: %s", $batch->name, $chunkCount, $batch->id);
        return $batch->id;
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
        $start = microtime(true);
        $jobs = $this->makeBatchJobs(DeleteImportedChunk::class, $ids);
        if (!count($jobs)) {
            $this->notice('No chunks to delete');
            return null;
        }
        $batch = Bus::batch($jobs)->name('Delete imports')->then(function (Batch $batch) use ($start) {
            Log::info(sprintf('[%s]: Deleted %d chunks from DB in %.2f seconds. Batch ID: %s', $batch->name, $batch->totalJobs, microtime(true) - $start, $batch->id));
        })->catch(function (Batch $batch, Throwable $except) {
            Log::error(sprintf('[%s]: On Batch %s: %s', $batch->name, $batch->id, $except->getMessage()));
        })->allowFailures()->onQueue('data')->dispatch();
        $this->info("[%s]: Batch initiated on %d chunks with batch ID: %s", $batch->name, $batch->totalJobs, $batch->id);
        return $batch->id;
    }

    /**
     * Create Batch job for these chunks.
     *
     * @param string $jobClass Job to work on chunks
     * @param int[] $ids Chunk model IDs.
     * @param Closure $filter Filter chunk collections using this callback
     *
     * @return array
     */
    protected function makeBatchJobs($jobClass, $ids, Closure $filter = null): array
    {
        $jobs = [];
        $models = $this->getChunkModels($ids);
        if ($filter) {
            $models->filter($filter);
        }
        foreach ($this->getChunkModels($ids) as $chunk) {
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
        $query = Chunk::whereIn('id', $ids)
            ->whereNot('fetch_status', 'in_progress')
            ->whereNotIn('import_status', $this->forceImport ? ['in_progress'] : ['in_progress', 'finished']);
        return $query->get()->reduce(function (?array $jobs, Chunk $chunk) {
            $jobs[] = ($this->forceFetch || $chunk->fetch_status !== 'finished') ? [
                new FetchChunk($chunk->id),
                new ImportChunk($chunk->id),
            ] : new ImportChunk($chunk->id);
            return $jobs;
        }, []);
    }

    /**
     * Get the models for given chunk IDs.
     *
     * @param int[] $chunkIds The model ID of chunks.
     *
     * @return Collection
     */
    protected function getChunkModels($chunkIds): Collection
    {
        $chunks = Chunk::where('sink_id', $this->sinkId)->whereIn('id', $chunkIds)->get();
        if ($chunks->count() !== count($chunkIds)) {
            throw new Exception(sprintf('Mismatch between requested (%d) and actual chunks (%d)', count($chunkIds), $chunks->count()));
        }
        return $chunks;
    }
}
