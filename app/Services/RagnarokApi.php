<?php

namespace App\Services;

use App\Models\Sink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Service for operating on sinks
 */
class RagnarokApi
{
    /**
     * @var Collection $sinks
     */
    protected $handlers = null;

    /**
     * @return Collection
     */
    public function getSinkHandlers(): Collection
    {
        if ($this->handlers !== null) {
            return $this->handlers;
        }
        $this->handlers = Sink::all()->reduce(
            fn (Collection $result, $sink) => $result->put($sink->id, new SinkHandler($sink)),
            collect()
        );
        return $this->handlers;
    }

    /**
     * @param string $sinkId
     *
     * @return SinkHandler
     */
    public function getSinkHandler($sinkId): SinkHandler
    {
        return $this->getSinkHandlers()->get($sinkId);
    }

    /**
     * Setup scheduled tasks for sink imports.
     */
    public function schedule(Schedule $schedule): RagnarokApi
    {
        // This is ran/called during boot, and if DB isn't properly
        // migrated/installed this will choke.
        if (!Schema::hasTable('ragnarok_sinks')) {
            return $this;
        }
        $this->getSinkHandlers()->each(function (SinkHandler $handler) use ($schedule) {
            if (! $handler->sink->is_live) {
                return;
            }
            $importEvent = $schedule->call([$handler, 'importNewChunks']);
            if ($handler->src->cron) {
                $importEvent->cron($handler->src->cron);
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
        $this->getSinkHandlers()->each(fn (SinkHandler $handler) => $handler->importNewChunks());
        return $this;
    }
}
