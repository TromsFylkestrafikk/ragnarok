<?php

namespace App\Console\Commands;

use App\Services\Linter;
use Illuminate\Console\Command;

class RagnarokLint extends Command
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
        $linter = new Linter();
        $this->line('Cleaning up state in chunks table ...');
        $linter->chunkLinter();
        $this->line('Removing completed batch jobs ...');
        $linter->batchSinkLinter();
        $this->info('Done.');
    }
}
