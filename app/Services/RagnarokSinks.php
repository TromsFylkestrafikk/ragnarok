<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Service for operating on sinks
 */
class RagnarokSinks
{
    /**
     * @var Collection $sinks
     */
    protected $sinks = null;

    /**
     * @return Collection
     */
    public function getSinks(): Collection
    {
        if ($this->sinks !== null) {
            return $this->sinks;
        }
        $sinks = [];
        foreach (config('ragnarok.sinks') as $sinkClass) {
            $sinks[] = new RagnarokSink(new $sinkClass());
        }
        $this->sinks = collect($sinks)->keyBy(fn ($sink) => $sink->src->id);
        return $this->sinks;
    }

    /**
     * @param string $sinkId
     *
     * @return RagnarokSink
     */
    public function getSink($sinkId): RagnarokSink
    {
        return $this->getSinks()->get($sinkId);
    }

    /**
     * Setup scheduled tasks for sink imports.
     */
    public function schedule(Schedule $schedule): RagnarokSinks
    {
        $this->getSinks()->each(function (RagnarokSink $sink) use ($schedule) {
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
    public function updateAll(): RagnarokSinks
    {
        $this->getSinks()->each(fn ($sink) => /** @var RagnarokSink $sink */ $sink->importNewChunks());
        return $this;
    }
}
