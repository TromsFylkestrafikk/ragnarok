<?php

namespace App\Jobs;

use App\Events\ChunkOperationUpdate;
use Illuminate\Bus\Batch;

trait BroadcastsBatch
{
    /**
     * Broadcasts batch if number of jobs is more than one.
     *
     * @param string $sinkId
     * @param Batch $batch
     * @param int $calibrate Adjust the number of processed jobs with this.
     *
     * @return void
     */
    public static function broadcast($sinkId, Batch $batch, $calibrate = 0): void
    {
        $calibrated = false;
        if ($batch->totalJobs > 1) {
            // This is a hack, but when broadcasting batch state from within an
            // almost finished job, the pendingJobs attribute isn't updated
            // correctly, so we allow for this to be calibrated.
            if ($calibrate && $batch->processedJobs() < $batch->totalJobs) {
                $batch->pendingJobs -= $calibrate;
                $calibrated = true;
            }
            ChunkOperationUpdate::dispatch($sinkId, $batch);
            if ($calibrated) {
                $batch->pendingJobs += $calibrate;
            }
        }
    }
}
