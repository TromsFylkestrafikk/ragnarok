<?php

namespace App\Http\Controllers;

use App\Facades\Ragnarok;
use App\Jobs\FetchChunks;
use App\Jobs\ImportChunks;
use App\Jobs\RemoveChunks;
use App\Jobs\DeleteImportedChunks;
use App\Models\Chunk;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
     *
     * @param Request $request
     * @param string $sinkId
     *
     * @return Response
     */
    public function fetch(Request $request, $sinkId)
    {
        FetchChunks::dispatch($sinkId, (array) $request->input('ids'));
        return response(['message' => 'Fetch job dispatched', 'status' => true]);
    }

    /**
     * Import chunks to DB
     *
     * @param Request $request
     * @param string $sinkId
     *
     * @return Response
     */
    public function import(Request $request, $sinkId)
    {
        ImportChunks::dispatch($sinkId, (array) $request->input('ids'));
        return response(['message' => 'Import job dispatched', 'status' => true]);
    }

    /**
     * @param Request $request
     * @param string $sinkId
     *
     * @return Response
     */
    public function deleteImport(Request $request, $sinkId)
    {
        DeleteImportedChunks::dispatch($sinkId, (array) $request->input('ids'));
        return response(['message' => 'Deletion of import job dispatched', 'status' => true]);
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
