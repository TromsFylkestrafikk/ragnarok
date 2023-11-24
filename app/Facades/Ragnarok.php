<?php

namespace App\Facades;

use App\Services\RagnarokApi;
use App\Services\SinkHandler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Console\Scheduling\Schedule;

/**
 * @method static Collection  getSinkHandlers()            Get all available sinks.
 * @method static RagnarokApi syncSinkModels()             Populate missing sink models, if needed.
 * @method static SinkHandler getSinkHandler(string $sinkId) Get the sink service for given sink ID.
 * @method static RagnarokApi updateAll()                  Fetches and imports newest chunks from all sinks.
 * @method static RagnarokApi schedule(Schedule $schedule) Set up scheduling for sink imports.
 */
class Ragnarok extends Facade
{
    public static function getFacadeAccessor()
    {
        return RagnarokApi::class;
    }
}
