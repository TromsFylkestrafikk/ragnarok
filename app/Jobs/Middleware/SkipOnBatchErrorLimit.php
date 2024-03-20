<?php

namespace App\Jobs\Middleware;

use App\Models\Chunk;
use App\Services\ChunkDispatcher;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Log;

class SkipOnBatchErrorLimit
{
    public function __construct(
        /**
         * Max number of failures.
         */
        public int $limit = 5,
        /**
         * If this is '%', limit is in percentage of total jobs
         */
        public string|null $unit = null
    ) {
        //
    }

    /**
     * @param callable $next
     */
    public function handle($job, $next): void
    {
        /** @var Batch $batch */
        $batch = $job->batch();
        $limit = $this->unit === '%' ? $this->limit * $batch->totalJobs / 100 : $this->limit;
        Log::warning(sprintf("[SkipOnBatchErrorLimit]: limit: %d, failed: %d", $limit, $batch->failedJobs));
        if ($batch->failedJobs >= $limit) {
            if (!$batch->cancelled()) {
                Log::error(sprintf(
                    "[SkipOnBatchErrorLimit]: Too many failed jobs (%d / %d). Cancelling.",
                    $limit,
                    $batch->totalJobs
                ));
                $chunk = Chunk::findOrFail($job->modelId);
                $dispatcher = new ChunkDispatcher($chunk->sink);
                $dispatcher->cancelBatch($batch);
            }
            return;
        }
        $next($job);
    }
}
