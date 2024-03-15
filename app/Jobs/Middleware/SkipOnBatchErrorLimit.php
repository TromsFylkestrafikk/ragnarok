<?php

namespace App\Jobs\Middleware;

use App\Models\Chunk;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\Job;
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
        if ($batch->failedJobs >= $limit) {
            if (!$batch->cancelled()) {
                Log::error(sprintf(
                    '[SkipOnBatchErrorLimit]: Too many failed jobs (%d / %d). Cancelling.',
                    $limit,
                    $batch->totalJobs
                ));
                $batch->cancel();
                // Remove batch info on non-running chunks.
                // Usually, this should be cleaned up in the ->finally() handler
                // of the dispatched batch, but for some reason it does not.
                Chunk::where('fetch_batch', $batch->id)
                    ->orWhere('import_batch', $batch->id)
                    ->whereNot('fetch_status', 'in_progress')
                    ->whereNot('import_status', 'in_progress')
                    ->get()
                    ->each(function (Chunk $chunk) {
                        $chunk->fetch_batch = null;
                        $chunk->import_batch = null;
                        $chunk->save();
                    });
            }
            return;
        }
        $next($job);
    }
}
