<?php

namespace App\Jobs;

use App\Facades\Ragnarok;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportChunks implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param string $sinkId Sink identifier
     * @param array $chunkIds List of chunks to import
     */
    public function __construct(protected string $sinkId, protected array $chunkIds)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Ragnarok::getSink($this->sinkId)->importChunks($this->chunkIds);
    }
}
