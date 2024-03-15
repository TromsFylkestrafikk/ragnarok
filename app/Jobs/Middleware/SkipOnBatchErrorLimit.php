<?php

namespace App\Jobs\Middleware;

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
            }
            return;
        }
        $next($job);
    }
}
