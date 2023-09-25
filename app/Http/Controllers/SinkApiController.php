<?php

namespace App\Http\Controllers;

use App\Facades\Ragnarok;
use App\Http\Resources\SinkCollection;
use App\Http\Resources\SinkResource;
use App\Models\Sink;

class SinkApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('sinks');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new SinkCollection(Sink::all());
    }

    /**
     * Display the specified resource.
     */
    public function show(Sink $sink)
    {
        return new SinkResource($sink);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Sink $sink)
    {
        return response([
            'message' => 'Import job dispatched',
            'status' => true,
            'batchId' => Ragnarok::getSinkHandler($sink->id)->importNewChunks(),
        ]);
    }
}
