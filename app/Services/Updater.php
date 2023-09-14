<?php

namespace App\Services;

use App\Facades\Ragnarok;
use App\Services\RagnarokSink;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Tools around keeping sinks updated and imported
 */
class Updater
{
    /**
     * Setup scheduled tasks for sink imports.
     */
    public function schedule(Schedule $schedule): Updater
    {
        Ragnarok::getSinks()->each(function (RagnarokSink $sink) use ($schedule) {
            $importEvent = $schedule->call([$sink, 'importNewChunks']);
            if ($sink->src->cron) {
                $importEvent->cron($sink->src->cron);
            } else {
                $importEvent->dailyAt('10:35');
            }
        });
        return $this;
    }

    /**
     * @return $this
     */
    public function updateAll(): Updater
    {
        Ragnarok::getSinks()->each(fn ($sink) => /** @var RagnarokSink $sink */ $sink->importNewChunks());
        return $this;
    }
}
