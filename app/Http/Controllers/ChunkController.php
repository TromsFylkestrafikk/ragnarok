<?php

namespace App\Http\Controllers;

use App\Models\Sink;
use App\Http\Resources\ChunkCollection;
use App\Services\ChunkDispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class ChunkController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Sink $sink
     *
     * @return ChunkCollection
     */
    public function index(Request $request, Sink $sink): ChunkCollection
    {
        $perPage = $request->input('itemsPerPage') ?: null;
        $sortBy = $request->input('sortBy') ?: null;
        /** @var Builder */
        $query = $sink->chunks();
        if ($sortBy) {
            $query->orderBy($sortBy[0]['key'], $sortBy[0]['order']);
        }
        return new ChunkCollection($query->orderBy('chunk_id', 'desc')->paginate($perPage));
    }

    /**
     * Fetch chunks to local storage.
     *
     * @param Request $request
     * @param Sink $sink
     *
     * @return Response
     */
    public function fetch(Request $request, Sink $sink): Response
    {
        return response([
            'message' => 'Fetch jobs dispatched',
            'status' => true,
            'batchId' => (new ChunkDispatcher($sink->id))->fetchChunks($request->input('ids')),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteFetched(Request $request, Sink $sink): Response
    {
        return response([
            'message' => 'Chunks removal job dispatched',
            'status' => true,
            'batchId' => (new ChunkDispatcher($sink->id))->removeChunks($request->input('ids')),
        ]);
    }

    /**
     * Import chunks to DB
     *
     * @param Request $request
     * @param Sink $sink
     *
     * @return Response
     */
    public function import(Request $request, Sink $sink): Response
    {
        return response([
            'message' => 'Import jobs dispatched',
            'status' => true,
            'batchId' => (new ChunkDispatcher($sink->id))->importChunks($request->input('ids')),
        ]);
    }

    /**
     * @param Request $request
     * @param Sink $sink
     *
     * @return Response
     */
    public function deleteImported(Request $request, Sink $sink): Response
    {
        return response([
            'message' => 'Deletion of import job dispatched',
            'status' => true,
            'batchId' => (new ChunkDispatcher($sink->id))->deleteImports($request->input('ids')),
        ]);
    }
}
