<?php

namespace App\Jobs;

use App\Facades\Ragnarok;
use App\Models\Chunk;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;

class ImportChunk implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int $modelId Model ID of chunk to import
     */
    public function __construct(public int $modelId)
    {
        $this->onQueue('data');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }
        $chunk = Chunk::find($this->modelId);
        if (!$chunk) {
            return;
        }
        Ragnarok::getSinkHandler($chunk->sink_id)->importChunk($chunk);
    }

    /**
     * @return mixed[]
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping(sprintf('chunk-import-%d', $this->modelId)),
            new SkipIfBatchCancelled(),
        ];
    }
}
