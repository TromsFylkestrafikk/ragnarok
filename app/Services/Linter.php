<?php

namespace App\Services;

use App\Models\BatchSink;
use App\Models\Chunk;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Ragnarok\Sink\Traits\LogPrintf;

/**
 * Tools for cleaning up unlikely state in chunk and batches tables.
 */
class Linter
{
    use LogPrintf;

    public function __construct()
    {
        $this->logPrintfInit('[Chunk Linter]: ');
    }

    public function chunkLinter(): Linter
    {
        $newerThan = now()->subHour();
        /** @var \Illuminate\Support\Collection<string, array> $batches */
        $batches = DB::table('job_batches')
            ->whereNull('finished_at')
            ->orWhere('finished_at', '>', $newerThan->getTimestamp())
            ->get()
            ->keyBy('id');
        // Case 1: Chunks members of batches that are finished/dead.
        /** @var Collection<string, Chunk> $chunks */
        $chunks = Chunk::whereNotNull('fetch_batch')->orWhereNotNull('import_batch')->get();
        foreach ($chunks as $chunk) {
            $batchId = $chunk->fetch_batch ?: $chunk->import_batch;
            if ($batchId && empty($batches[$batchId])) {
                $this->notice("%s: Removing chunk %s from stale batch job", $chunk->sink_id, $chunk->chunk_id);
                $chunk->fetch_batch = $chunk->import_batch = null;
                $chunk->save();
            }
        }
        if (!count($chunks)) {
            $this->debug('No references to finished batches/jobs found. Good!');
        }

        // Case 2: Chunks in progress where batches are gone/stale.
        /** @var Collection<Chunk> $chunks */
        $chunks = Chunk::where('fetch_status', 'in_progress')->orWhere('import_status', 'in_progress')->get();
        foreach ($chunks as $chunk) {
            $stage = $chunk->fetch_status === 'in_progress' ? 'fetch' : 'import';
            if ($chunk->updated_at->isBefore($newerThan)) {
                $this->notice("%s: Chunk '%s' seems to have stalled. Setting %s state to 'failed'", $chunk->sink_id, $chunk->chunk_id, $stage);
                $chunk->{$stage . '_status'} = 'failed';
                $chunk->{$stage . '_message'} = 'Operation has stalled or an invalid chunk state is detected.';
                $chunk->save();
            }
        }
        if (!count($chunks)) {
            $this->debug('No stale chunks in progress found. Good!');
        }

        return $this;
    }

    public function batchSinkLinter(): Linter
    {
        // Remove BatchSinks where batches are finished/complete
        $batchIds = DB::table('ragnarok_batches', 'rb')
            ->select('batch_id')
            ->join('job_batches as jb', 'rb.batch_id', 'jb.id')
            ->whereNotNull('jb.finished_at')
            ->pluck('batch_id')
            ->toArray();
        BatchSink::whereIn('batch_id', $batchIds)->delete();
        if (count($batchIds)) {
            $this->notice('Removed %d finished batch jobs from `ragnarok_batches` table', count($batchIds));
        } else {
            $this->debug('No inconsistency between ragnarcok batches and laravel batches found. Good!');
        }
        return $this;
    }
}
