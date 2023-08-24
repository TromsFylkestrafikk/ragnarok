<?php

namespace App\Services;

use Illuminate\Support\Collection;

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
     * @return Collection
     */
    public function getSinksJson(): Collection
    {
        return $this->getSinks()->map(fn ($sink) => [
            'id' => $sink->src->id,
            'title' => $sink->src->title,
            'lastImport' => $sink->lastImport(),
        ])->values();
    }
}
