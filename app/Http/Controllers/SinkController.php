<?php

namespace App\Http\Controllers;

use App\Facades\Ragnarok;
use App\Services\RagnarokSink;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class SinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('ImportStatus', [
            'sinks' => Ragnarok::getSinks()->map(fn (RagnarokSink $sink) => $sink->asClientSide())->values(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $sinkId)
    {
        if (!Gate::allows('read sources')) {
            abort(403);
        }
        return Inertia::render('SinkStatus', ['sink' => Ragnarok::getSink($sinkId)->asClientSide()]);
    }
}
