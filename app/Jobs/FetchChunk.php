<?php

namespace App\Jobs;

use App\Facades\Ragnarok;
use App\Models\Chunk;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TromsFylkestrafikk\RagnarokSink\Traits\LogPrintf;

class FetchChunk implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use LogPrintf;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param string $sinkId Sink identifier
     * @param array $chunkIds List of chunks to fetch
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
        $this->logPrintfInit("[Sink Fetch]: ");
        /** @var \App\Services\RagnarokSink */
        $sink = Ragnarok::getSink($this->sinkId);
        $chunks = Chunk::where('sink_id', $this->sinkId)->whereIn('id', $this->chunkIds)->get();
        if ($chunks->count() !== count($this->chunkIds)) {
            throw new Exception('Mismatch between requested and actual chunks');
        }
        $start = microtime(true);
        $lapStart = $start;
        $this->debug('Fetching %d chunks ...', $chunks->count());
        foreach ($chunks as $chunk) {
            $chunk->fetch_status = 'in_progress';
            $chunk->imported_at = null;
            $chunk->save();
            $chunk->fetch_status = $sink->src->fetch($chunk->chunk_id) ? 'finished' : 'failed';
            $chunk->fetched_at = now();
            $chunk->save();
            $lapTime = microtime(true);
            $this->debug('Fetched chunk %s in %.2f seconds', $chunk->chunk_id, $lapTime - $lapStart);
            $lapStart = $lapTime;
        }
        $this->info(
            "%s: Fetched %d chunks in %.2f seconds",
            $this->sinkId,
            count($this->chunkIds),
            microtime(true) - $start
        );
    }
}
