<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Console\Scheduling\Schedule;
use Ragnarok\Sink\Facades\SinkRegistrar;

/**
 * Service for operating on sinks
 */
class RagnarokApi
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
        foreach (SinkRegistrar::getSinkClasses() as $sinkClass) {
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
    public function schedule(Schedule $schedule): RagnarokApi
    {
        $this->getSinks()->each(function (RagnarokSink $sink) use ($schedule) {
            $importEvent = $schedule->call([$sink, 'importNewChunks']);
            if ($sink->src->cron) {
                $importEvent->cron($sink->src->cron);
            } else {
                $importEvent->dailyAt('05:00');
            }
        });
        return $this;
    }

    /**
     * @return $this
     */
    public function updateAll(): RagnarokApi
    {
        $this->getSinks()->each(fn ($sink) => /** @var RagnarokSink $sink */ $sink->importNewChunks());
        return $this;
    }
}
