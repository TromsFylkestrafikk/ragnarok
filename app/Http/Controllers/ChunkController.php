<?php

namespace App\Http\Controllers;

use App\Facades\Ragnarok;
use App\Jobs\FetchChunks;
use App\Jobs\RemoveChunks;
use App\Models\Chunk;
use Illuminate\Http\Request;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class ChunkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $sinkId)
    {
        $itemsPerPage = $request->query('itemsPerPage') ?: 20;
        $orderBy = $request->query('sortBy') ? $request->query('sortBy')[0] : null;
        return Ragnarok::getSink($sinkId)->getChunks($itemsPerPage, $orderBy);
    }

    /**
     * Fetch chunks to local storage.
     */
    public function fetch(Request $request, $sinkId)
    {
        $chunkIds = (array) $request->input('ids');
        FetchChunks::dispatch($sinkId, $chunkIds);
        return response(['message' => 'Fetch job dispatched', 'status' => true]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $chunkId)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $chunkId)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $sinkId)
    {
        $chunkIds = (array) $request->input('ids');
        RemoveChunks::dispatch($sinkId, $chunkIds);
        return response(['message' => 'Chunks removal job dispatched', 'status' => true]);
    }
}
