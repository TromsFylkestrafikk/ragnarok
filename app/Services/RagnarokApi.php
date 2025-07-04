<?php

namespace App\Services;

use App\Models\Sink;
use App\Models\Chunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Schedule;

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
    public function schedule(): RagnarokApi
    {
        // This is ran/called during boot, and if DB isn't properly
        // migrated/installed this will choke.
        if (!Schema::hasTable('ragnarok_sinks')) {
            return $this;
        }
        $this->getSinkHandlers()->each(function (SinkHandler $handler) {
            if (! $handler->sink->is_live) {
                return;
            }
            $this->setupImportSchedule($handler);
            $this->setupRefetchSchedule($handler);
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

    /**
     * Setup scheduled import for sink.
     */
    protected function setupImportSchedule(SinkHandler $handler): void
    {
        $importEvent = Schedule::call([$handler, 'importNewChunks']);
        if ($handler->src->cron !== null) {
            $importEvent->cron($handler->src->cron);
        } else {
            $importEvent->dailyAt('05:00');
        }
    }

    /**
     * Setup scheduled re-fetch of chunks from sink.
     */
    protected function setupRefetchSchedule(SinkHandler $handler): void
    {
        if (empty($handler->src->cronRefetch)) {
            return;
        }
        Schedule::call(function () use ($handler) {
            list ($fromChunkId, $toChunkId) = $handler->src->refetchIdRange();
            if (empty($fromChunkId || empty($toChunkId))) {
                return;
            }
            $ids = Chunk::select('id')
                ->where('sink_id', $handler->src::$id)
                ->where('chunk_id', '>=', $fromChunkId)
                ->where('chunk_id', '<=', $toChunkId)
                ->pluck('id')->toArray();
            // By forcing fetch this will be downloaded, and imported only if
            // they are modified.
            $handler->getChunkDispatcher()->setForceFetch()->import($ids);
        })->cron($handler->src->cronRefetch);
    }
}
