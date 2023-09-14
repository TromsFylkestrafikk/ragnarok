<?php

namespace App\Facades;

use App\Services\Updater as UpdaterReal;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void updateAll()                  Fetches and imports newest chunks from all sinks.
 * @method static void schedule(Schedule $schedule) Set up scheduling for sink imports.
 */
class Updater extends Facade
{
    public static function getFacadeAccessor()
    {
        return UpdaterReal::class;
    }
}
