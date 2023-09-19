<?php

namespace App\Http\Controllers;

use App\Facades\Ragnarok;
use App\Services\RagnarokSink;
use Illuminate\Http\Request;

class SinkApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Ragnarok::getSinks()->map(fn (RagnarokSink $sink) => $sink->asClientSide())->values();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $sinkId)
    {
        return Ragnarok::getSink($sinkId)->asClientSide();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $sinkId)
    {
        return response([
            'message' => 'Import job dispatched',
            'status' => true,
            'batchId' => Ragnarok::getSink($sinkId)->importNewChunks(),
        ]);
    }
}
