<?php

namespace App\Jobs;

use App\Facades\Ragnarok;
use App\Jobs\Middleware\SkipOnBatchErrorLimit;
use App\Models\Chunk;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class FetchChunk implements ShouldQueue
{
    use Batchable;
    use BroadcastsBatch;
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
        $chunk = Chunk::findOrFail($this->modelId);
        Ragnarok::getSinkHandler($chunk->sink_id)->fetchChunk($chunk);
        self::broadcast($chunk->sink_id, $this->batch(), 1);
    }

    /**
     * @return mixed[]
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(sprintf('chunk-%d-fetch', $this->modelId)))->dontRelease(),
            new SkipIfBatchCancelled(),
            new SkipOnBatchErrorLimit(config('ragnarok.max_batch_errors'), config('ragnarok.max_batch_errors_unit', null)),
        ];
    }
}
