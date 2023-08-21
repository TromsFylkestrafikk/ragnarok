<?php

namespace App\Http\Controllers;

use App\Facades\Ragnarok;
use App\Jobs\ImportChunk;
use App\Models\SinkImport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class ImportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Assert no other import is in progress
        $sinkId = $request->input('sink_id');
        $importing = SinkImport::running()->whereSinkId($sinkId)->first();
        if ($importing) {
            return response('Import already in progress', Response::HTTP_TOO_EARLY);
        }
        $import = SinkImport::create($request->all());
        $import->refresh();
        ImportChunk::dispatch($import);
        return $import;
    }

    /**
     * Display the specified resource.
     */
    public function show(SinkImport $sinkImport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SinkImport $sinkImport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SinkImport $sinkImport)
    {
        //
    }
}
