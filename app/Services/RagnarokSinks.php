<?php

namespace App\Services;

use Illuminate\Support\Collection;
use TromsFylkestrafikk\RagnarokSink\Sinks\Sink;

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
        $this->sinks = collect($sinks)->keyBy(fn ($sink) => $sink->src->name);
        return $this->sinks;
    }

    /**
     * @param string $sinkName
     *
     * @return RagnarokSink
     */
    public function getSink($sinkName): RagnarokSink
    {
        return $this->getSinks()->get($sinkName);
    }

    /**
     * @return Collection
     */
    public function getSinksJson(): Collection
    {
        return $this->getSinks()->map(fn ($sink) => [
            'name' => $sink->src->name,
            'lastImport' => $sink->lastImport(),
        ])->values();
    }
}
