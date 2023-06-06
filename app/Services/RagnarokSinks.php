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
     * @param Collection $sinks
     */
    public function __construct(protected $sinks = null)
    {
        //
    }

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
            $sinks[] = new $sinkClass();
        }
        $this->sinks = collect($sinks);
        return $this->sinks;
    }

    /**
     * @return Collection
     */
    public function getSinksJson(): Collection
    {
        return $this->getSinks()->map(fn ($sink) => ['name' => $sink->name]);
    }
}
