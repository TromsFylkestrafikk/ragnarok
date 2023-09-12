<?php

namespace App\Services;

use App\Jobs\DeleteImportedChunk;
use App\Jobs\FetchChunk;
use App\Jobs\ImportChunk;
use App\Jobs\RemoveChunk;
use App\Models\SinkImport;
use App\Models\Chunk;
use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;
use TromsFylkestrafikk\RagnarokSink\Sinks\SinkBase;
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
     * Get the models for given chunk IDs.
     *
     * @param int[] $chunkIds The model ID of chunks.
     *
     * @return Collection
     */
    public function getChunkModels($chunkIds): Collection
    {
        $chunks = Chunk::where('sink_id', $this->src->id)->whereIn('id', $chunkIds)->get();
        if ($chunks->count() !== count($chunkIds)) {
            throw new Exception(sprintf('Mismatch between requested (%d) and actual chunks (%d)', count($chunkIds), $chunks->count()));
        }
        return $chunks;
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
     * Fetch numerous chunks in bulk through batched jobs.
     *
     * @param int[] $ids
     *
     * @return string|null
     */
    public function importChunks($ids): string|null
    {
        $chunkCount = count($ids);
        $jobs = $this->makeBatchJobs(ImportChunk::class, $ids);
        if (!count($jobs)) {
            $this->notice('No chunks to import');
            return null;
        }
        $batch = Bus::batch($jobs)->name('Import chunks')->then(function (Batch $batch) use ($chunkCount) {
            Log::info(sprintf("[%s]: %d chunks successfully imported on batch ID: %s", $batch->name, $chunkCount, $batch->id));
        })->catch(function (Batch $batch, Throwable $except) {
            Log::error(sprintf('[%s]: On Batch %s: %s', $batch->name, $batch->id, $except->getMessage()));
        })->onQueue('data')->dispatch();
        $this->info("[%s]: Batch initiated on %d chunks with batch ID: %s", $batch->name, $chunkCount, $batch->id);
        return $batch->id;
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
}
