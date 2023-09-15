<?php

namespace App\Facades;

use App\Services\RagnarokApi;
use App\Services\RagnarokSink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Console\Scheduling\Schedule;

/**
 * @method static Collection   getSinks()                   Get all available sinks.
 * @method static RagnarokSink getSink(string $sinkId)      Get the sink service for given sink ID.
 * @method static RagnarokApi  updateAll()                  Fetches and imports newest chunks from all sinks.
 * @method static RagnarokApi  schedule(Schedule $schedule) Set up scheduling for sink imports.
 */
class Ragnarok extends Facade
{
    public static function getFacadeAccessor()
    {
        return RagnarokApi::class;
    }
}
