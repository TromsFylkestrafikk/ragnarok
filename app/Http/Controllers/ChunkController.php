<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListChunksRequest;
use App\Http\Requests\UpdateChunkRequest;
use App\Http\Resources\ChunkCollection;
use App\Http\Helpers\ChunksFilter;
use App\Models\Sink;
use App\Models\Chunk;
use App\Services\ChunkDispatcher;
use Illuminate\Http\Response;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class ChunkController extends Controller
{
    use ChunksFilter;

    /**
     * Display a listing of the resource.
     *
     * @param Sink $sink
     *
     * @return ChunkCollection
     */
    public function index(ListChunksRequest $request, Sink $sink): ChunkCollection
    {
        $query = $this->applyFilters($request->input(), $sink->chunks());
        $perPage = $request->input('itemsPerPage') ?: null;
        $sortBy = $request->input('sortBy') ?: null;
        if ($sortBy) {
            $query->orderBy($sortBy[0]['key'], $sortBy[0]['order']);
        }
        return new ChunkCollection($query->orderBy('chunk_id', 'desc')->paginate($perPage));
    }

    public function update(UpdateChunkRequest $request, Sink $sink, Chunk $chunk): Response
    {
        $operation = $request->input('operation');
        $batchId = (new ChunkDispatcher($sink->id))
            ->setForceFetch($request->input('forceFetch'))
            ->setForceImport($request->input('forceImport'))
            ->{$operation}([$chunk->id]);
        return response([
            'message' => $batchId ? "$operation job dispatched" : 'Nothing to do',
            'status' => (bool) $batchId,
            'batchId' => $batchId,
        ]);
    }
}
