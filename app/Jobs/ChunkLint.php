<?php

namespace App\Jobs;

use App\Models\Chunk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Ragnarok\Sink\Traits\LogPrintf;

/**
 * Clean up invalid and unlikely state
 */
class ChunkLint implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use LogPrintf;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->logPrintfInit('[Chunk Linter]: ');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $newerThan = now()->subHours(4);
        $batches = DB::table('job_batches')->whereNull('finished_at')->orWhere('finished_at', '>', $newerThan)->get()->keyBy('id');

        // Case 1: Chunks members of batches that are finished/dead.
        $chunks = Chunk::whereNotNull('fetch_batch')->orWhereNotNull('import_batch')->get();
        foreach ($chunks as $chunk) {
            /** @var Chunk $chunk */
            $batchId = $chunk->fetch_batch ?: $chunk->import_batch;
            if (!$batchId || empty($batches[$batchId])) {
                $this->debug("%s: Removing chunk %s from stale batch job", $chunk->sink_id, $chunk->chunk_id);
                $chunk->fetch_batch = $chunk->import_batch = null;
                $chunk->save();
            }
        }

        // Case 2: Chunks in progress where batches are gone/stale.
        $chunks = Chunk::where('fetch_status', 'in_progress')->orWhere('import_status', 'in_progress')->get();
        foreach ($chunks as $chunk) {
            /** @var Chunk $chunk */
            $stage = $chunk->fetch_status === 'in_progress' ? 'fetch' : 'import';
            if ($chunk->updated_at->isBefore($newerThan)) {
                $this->debug("%s: Chunk '%s' seems to have stalled. Setting %s state to 'failed'", $chunk->sink_id, $chunk->chunk_id, $stage);
                $chunk->{$stage . '_status'} = 'failed';
                $chunk->{$stage . '_message'} = 'Operation has stalled or an invalid chunk state is detected.';
                $chunk->save();
            }
        }
    }
}
