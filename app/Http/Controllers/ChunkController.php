<?php

namespace App\Http\Controllers;

use App\Facades\Ragnarok;
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
     * @param Request $request
     * @param string $sinkId
     *
     * @return array
     */
    public function index(Request $request, string $sinkId): array
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
    public function fetch(Request $request, $sinkId): Response
    {
        $batchId = Ragnarok::getSink($sinkId)->fetchChunks($request->input('ids'));
        return response(['message' => 'Fetch jobs dispatched', 'status' => true, 'batchId' => $batchId]);
    }

    /**
     * Import chunks to DB
     *
     * @param Request $request
     * @param string $sinkId
     *
     * @return Response
     */
    public function import(Request $request, $sinkId): Response
    {
        $batchId = Ragnarok::getSink($sinkId)->importChunks($request->input('ids'));
        return response(['message' => 'Import jobs dispatched', 'status' => true, 'batchId' => $batchId]);
    }

    /**
     * @param Request $request
     * @param string $sinkId
     *
     * @return Response
     */
    public function deleteImport(Request $request, $sinkId): Response
    {
        $batchId = Ragnarok::getSink($sinkId)->deleteImports($request->input('ids'));
        return response(['message' => 'Deletion of import job dispatched', 'status' => true, 'batchId' => $batchId]);
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
    public function destroy(Request $request, string $sinkId): Response
    {
        $batchId = Ragnarok::getSink($sinkId)->removeChunks($request->input('ids'));
        return response(['message' => 'Chunks removal job dispatched', 'status' => true, 'batchId' => $batchId]);
    }
}
