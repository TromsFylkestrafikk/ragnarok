<?php

namespace App\Console;

use App\Facades\Ragnarok;
use App\Jobs\ChunkLint;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        Ragnarok::schedule($schedule);
        $schedule->command('queue:prune-batches --hours=48 --unfinished=72 --cancelled=72')->daily();
        $schedule->job(ChunkLint::class)->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
