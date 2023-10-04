<?php

namespace App\Http\Controllers;

use App\Facades\Ragnarok;
use App\Http\Resources\SinkCollection;
use App\Http\Resources\SinkResource;
use App\Http\Helpers\ChunksFilter;
use App\Models\Sink;
use App\Services\ChunkDispatcher;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class SinkApiController extends Controller
{
    use ChunksFilter;

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
     * Update chunks belonging to this sink.
     */
    public function update(Request $request, Sink $sink): Response
    {
        $request->validate([
            'operation' => [
                'required',
                Rule::in(['importNew', 'fetch', 'import', 'deleteFetched', 'deleteImported']),
            ],
            'targetSet' => ['required', Rule::in(['selection', 'range'])],
            'forceFetch' => 'boolean',
            'forceImport' => 'boolean',
        ]);
        $operation = $request->input('operation');
        return response([
            'message' => "$operation job dispatched",
            'status' => true,
            'batchId' => $this->executeOperation($request, $sink, $operation),
        ]);
    }

    /**
     * @return string Batch ID of executed batch operation.
     */
    protected function executeOperation(Request $request, Sink $sink, string $operation)
    {
        if ($operation === 'importNew') {
            return Ragnarok::getSinkHandler($sink->id)->importNewChunks();
        }
        return (new ChunkDispatcher($sink->id))
            ->setForceFetch((bool) $request->input('forceFetch'))
            ->setForceImport((bool) $request->input('forceImport'))
            ->{$operation}($this->getChunkIdsFromRequest($request, $sink));
    }

    /**
     * Get a list of chunk IDs based on input filters.
     *
     * @return array
     */
    protected function getChunkIdsFromRequest(Request $request, Sink $sink)
    {
        $query = $this->applyFilters($request->input(), $sink->chunks());
        return $this->applyTargetFilters($request, $query)->get()->pluck('id')->toArray();
    }

    /**
     * @param Builder $query
     *
     * @return Builder
     */
    protected function applyTargetFilters(Request $request, $query)
    {
        if ($request->input('targetSet') === 'selection') {
            $query->whereIn('id', $request->input('selection'));
        }
        return $query;
    }
}
