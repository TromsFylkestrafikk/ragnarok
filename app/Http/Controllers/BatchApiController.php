<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;

class BatchApiController extends Controller
{
    public function index(Request $request): Collection
    {
        $this->authorize('read sinks');
        $query = DB::table('job_batches')
            ->select('id')
            ->where('total_jobs', '>', 1)
            ->whereNot('pending_jobs', 0)
            ->whereNull('cancelled_at')
            ->whereNull('finished_at');
        // This is a bit hacky. But instead of having an additional table
        // linking batches to sinks, we scan for the sink ID in the batch name,
        // specified in App\Services\ChunkDispatcher.
        if ($request->input('sinkId')) {
            $query->where('name', 'LIKE', sprintf('%s: %%', $request->input('sinkId')));
        }
        return $query->pluck('id')->map(fn ($batchId) => Bus::findBatch($batchId));
    }

    /**
     * Cancel running batch
     *
     * @param string $batchId
     *
     * @return Response
     */
    public function destroy($batchId): Response
    {
        $this->authorize('delete batches');
        $batch = Bus::findBatch($batchId);
        if (!$batch) {
            return response('Not found', Response::HTTP_NOT_FOUND);
        }
        if ($batch->finished()) {
            return response(['status' => false, 'message' => 'Batch is not running', 'batchId' => $batch->id]);
        }
        $batch->cancel();
        return response(['status' => true, 'message' => 'Cancel signal sent to running batch', 'batchId' => $batch->id]);
    }
}
