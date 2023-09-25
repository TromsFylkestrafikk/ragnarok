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

class DeleteImportedChunk implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int $modelId ID of chunk model to delete imported data for
     */
    public function __construct(public int $modelId)
    {
        //
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
        Ragnarok::getSinkHandler($chunk->sink_id)->deleteImport($chunk);
    }

    /**
     * @return mixed[]
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(sprintf('chunk-%d-del-imported', $this->modelId)))->dontRelease(),
            new SkipIfBatchCancelled(),
        ];
    }
}
