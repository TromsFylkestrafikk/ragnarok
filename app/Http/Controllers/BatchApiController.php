<?php

namespace App\Http\Controllers;

use App\Models\BatchSink;
use App\Models\Chunk;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Routing\ResponseFactory;
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
        $query = DB::table('job_batches', 'jb')
            ->join('ragnarok_batches as rb', 'rb.batch_id', 'jb.id')
            ->select('jb.id', 'rb.*')
            ->where('jb.total_jobs', '>', 1)
            ->whereNot('jb.pending_jobs', 0)
            ->whereNull('jb.cancelled_at')
            ->whereNull('jb.finished_at');
        if ($request->input('sinkId')) {
            $query->where('rb.sink_id', $request->input('sinkId'));
        }
        return $query->get()->map(function ($row) {
            $batch = Bus::findBatch($row->batch_id)->toArray();
            $batch['sink_id'] = $row->sink_id;
            return $batch;
        });
    }

    public function show(string $batchId): array|null
    {
        $this->authorize('read sinks');
        $bSink = BatchSink::firstWhere(['batch_id' => $batchId])->get();
        $batch = Bus::findBatch($batchId);
        if (!$batch || !$bSink) {
            abort(Response::HTTP_NOT_FOUND);
        }
        /** @var BatchSink $bSink */
        $batch = $batch->toArray();
        $batch['sink_id'] = $bSink->sink_id;
        return $batch;
    }

    /**
     * Cancel running batch
     *
     * @param string $batchId
     *
     * @return Response
     */
    public function destroy($batchId): Response|ResponseFactory
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
        return response([
            'status' => true,
            'message' => 'Batch cancelled',
            'batch' => Bus::findBatch($batch->id)
        ]);
    }
}
