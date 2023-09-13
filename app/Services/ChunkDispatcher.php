<?php

namespace App\Services;

use App\Jobs\DeleteImportedChunk;
use App\Jobs\FetchChunk;
use App\Jobs\ImportChunk;
use App\Jobs\RemoveChunk;
use App\Models\Chunk;
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
 */
class ChunkDispatcher
{
    use LogPrintf;

    public function __construct(protected string $sinkId)
    {
        $this->logPrintfInit("[ChunkDispatcher %s]: ", $sinkId);
    }

    /**
     * Fetch numerous chunks in bulk through batched jobs.
     *
     * @param int[] $ids
     *
     * @return string|null
     */
    public function fetchChunks($ids): string|null
    {
        $chunkCount = count($ids);
        $jobs = $this->makeBatchJobs(FetchChunk::class, $ids);
        if (!count($jobs)) {
            $this->notice('No chunks to fetch');
            return null;
        }
        // The batch is serialized. No use of $this in callbacks.
        $batch = Bus::batch($jobs)->name('Fetch chunks')->then(function (Batch $batch) use ($chunkCount) {
            Log::info(sprintf('[%s]: Successfully retrieved %d chunks from sink. (Batch %s)', $batch->name, $chunkCount, $batch->id));
        })->catch(function (Batch $batch, Throwable $except) {
            Log::error(sprintf('[%s]: On Batch %s: %s', $batch->name, $batch->id, $except->getMessage()));
        })->onQueue('data')->dispatch();
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
    public function removeChunks($ids): string|null
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
        })->onQueue('data')->dispatch();
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
    public function importChunks($ids): string|null
    {
        $chunkCount = count($ids);
        $jobs = $this->makeBatchJobs(ImportChunk::class, $ids);
        $start = microtime(true);
        if (!count($jobs)) {
            $this->notice('No chunks to import');
            return null;
        }
        $batch = Bus::batch($jobs)->name('Import chunks')->then(function (Batch $batch) use ($start) {
            Log::info(sprintf(
                "[%s]: %d chunks successfully imported in %.2f seconds. Batch ID: %s",
                $batch->name,
                $batch->totalJobs,
                microtime(true) - $start,
                $batch->id
            ));
        })->catch(function (Batch $batch, Throwable $except) {
            Log::error(sprintf('[%s]: On Batch %s: %s', $batch->name, $batch->id, $except->getMessage()));
        })->onQueue('data')->dispatch();
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
    public function deleteImports($ids): string|null
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
        })->onQueue('data')->dispatch();
        $this->info("[%s]: Batch initiated on %d chunks with batch ID: %s", $batch->name, $batch->totalJobs, $batch->id);
        return $batch->id;
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
        foreach ($this->getChunkModels($ids) as $chunk) {
            /** @var Chunk $chunk  */
            $jobs[] = new $jobClass($chunk->id);
        };
        return $jobs;
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
