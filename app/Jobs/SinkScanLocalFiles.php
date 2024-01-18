<?php

namespace App\Jobs;

use App\Models\Chunk;
use App\Facades\Ragnarok;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Ragnarok\Sink\Models\SinkFile;
use Ragnarok\Sink\Services\SinkDisk;
use Ragnarok\Sink\Services\LocalFile;

class SinkScanLocalFiles implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable;
    use \Illuminate\Queue\InteractsWithQueue;
    use \Illuminate\Bus\Queueable;
    use \Illuminate\Queue\SerializesModels;
    use \Ragnarok\Sink\Traits\LogPrintf;

    /**
     * Create a new job instance.
     */
    public function __construct(protected string $sinkId)
    {
        $this->logPrintfInit('[%s Local Scan]: ', $sinkId);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->debug('BEGIN scan ...');
        /** @var Collection<string, Chunk> */
        $chunksWoFiles = Chunk::whereSinkId($this->sinkId)->doesntHave('sinkFile')->get()->keyBy('chunk_id');
        /** @var Collection<string, SinkFile> */
        $sinkFiles = SinkFile::whereSinkId($this->sinkId)->get()->keyBy('name');
        $sinkDisk = new SinkDisk($this->sinkId);
        $sinkHandler = Ragnarok::getSinkHandler($this->sinkId);
        $new = 0;
        $disconnected = 0;
        foreach ($sinkDisk->files() as $path) {
            $filename = basename($path);

            $sinkFile = $sinkFiles->has($path) ? $sinkFiles[$path] : null;
            if ($sinkFile) {
                // File is already managed. Won't touch it.
                continue;
            }
            $chunkId = $sinkHandler->src->filenameToChunkId($filename);
            if (!$chunkId) {
                $disconnected++;
                continue;
            }
            $chunk = $chunksWoFiles->has($chunkId) ? $chunksWoFiles[$chunkId] : null;
            if (! $chunk) {
                $this->error('Got chunk which isnt initialized: %s', $chunkId);
                continue;
            }
            $this->restoreFile($filename, $chunk);
            $new++;
        }
        $this->info('FINISHED. Added %d files. Disconnected files found: %d', $new, $disconnected);
    }

    /**
     * Restore file and map it to given chunk.
     */
    protected function restoreFile(string $filename, Chunk $chunk): void
    {
        $localFile = LocalFile::createFromFilename($this->sinkId, $filename);
        $localFile->save();

        $chunk->sink_file_id = $localFile->getFile()->id;
        // This is a new, previousy unseen file, so we set the fetch
        // status to finished.
        $chunk->fetch_status = 'finished';
        $chunk->fetch_message = null;
        $chunk->fetched_at = now();
        $chunk->save();
    }
}
