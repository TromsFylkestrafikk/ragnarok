<?php

namespace App\Console\Commands;

use App\Jobs\ChunkLint;
use Illuminate\Console\Command;

class ChunkLinter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ragnarok:lint';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix stuck/impossible states in chunks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ChunkLint::dispatch();
        $this->info('Done. Check tables or web interface for updates');
    }
}
