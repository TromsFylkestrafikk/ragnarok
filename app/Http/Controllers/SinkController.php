<?php

namespace App\Http\Controllers;

use App\Facades\Ragnarok;
use App\Services\RagnarokSink;
use Illuminate\Http\Request;
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
        if (!Gate::allows('read sources')) {
            abort(403);
        }

        return Inertia::render('ImportStatus', [
            'sinks' => Ragnarok::getSinks()->map(fn ($sink) => [
                /** @var RagnarokSink $sink */
                'id' => $sink->src->id,
                'title' => $sink->src->title,
                'newChunks' => $sink->getNewChunks()->count(),
                'lastImport' => $sink->lastImport(),
            ])->values(),
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
        return Inertia::render('SinkStatus', ['sink' => Ragnarok::getSink($sinkId)->getState()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $sinkId)
    {
        if (!Gate::allows('import sources')) {
            abort(403);
        }
    }
}
