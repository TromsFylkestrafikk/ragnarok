<?php

namespace App\Jobs;

use App\Facades\Ragnarok;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use App\Models\Chunk;

class FetchChunk implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int $modelId Model ID of chunk to fetch data for
     */
    public function __construct(protected int $modelId)
    {
        $this->onQueue('data');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $chunk = Chunk::find($this->modelId);
        if (!$chunk) {
            return;
        }
        Ragnarok::getSink($chunk->sink_id)->fetchChunk($chunk);
    }

    /**
     * @return mixed[]
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(sprintf('chunk-fetch-%d', $this->modelId)))->dontRelease(),
            new SkipIfBatchCancelled(),
        ];
    }
}