<?php

namespace App\Http\Controllers;

use App\Facades\Ragnarok;
use App\Http\Requests\SinkOperationRequest;
use App\Http\Resources\SinkCollection;
use App\Http\Resources\SinkResource;
use App\Http\Helpers\ChunksFilter;
use App\Models\Sink;
use App\Services\ChunkDispatcher;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ragnarok\Sink\Traits\LogPrintf;

class SinkApiController extends Controller
{
    use ChunksFilter;

    public function __construct()
    {
        $this->authorizeResource(Sink::class, 'sink');
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

    public function update(Request $request, Sink $sink): Response
    {
        $sink->fill($request->input())->save();
        Ragnarok::getSinkHandler($sink->id)->flushCache();
        return response([
            'status' => true,
            'sink' => $sink,
        ]);
    }

    /**
     * Update chunks belonging to this sink.
     */
    public function operation(SinkOperationRequest $request, Sink $sink): Response
    {
        $operation = $request->input('operation');
        $batchId = $this->executeOperation($request, $sink, $operation);
        return response([
            'message' => $batchId ? "$operation job dispatched with ID: $batchId" : 'Nothing to do',
            'status' => (bool) $batchId,
            'batchId' => $batchId,
        ]);
    }

    /**
     * Scan local file storage for files not already fetched.
     */
    public function scanLocalFiles(Sink $sink): Response
    {
        $this->authorize('update', $sink);
        Ragnarok::getSinkHandler($sink->id)->scanLocalFiles();
        /** @var Response */
        return response([
            'status' => true,
            'message' => sprintf('Initiated local file scan for sink %s as background job', $sink->title),
        ]);
    }

    /**
     * Fetches documentation from sink. Sets Response statuscode to 204 (no content)
     * if sink can't find documentat, or 200 (OK) if documentation is found
     *
     * @return Response object
     */
    public function getDoc(Sink $sink): Response
    {
        $this->authorize('view', $sink);
        $doc = Ragnarok::getSinkHandler($sink->id)->getSinkDocumentation();
        return response($doc, $doc === null ? 204 : 200)->header('Content-Type', 'text/html');
    }

    /**
     * @return string|null Batch ID of executed batch operation.
     */
    protected function executeOperation(Request $request, Sink $sink, string $operation): string|null
    {
        if ($operation === 'importNew') {
            return Ragnarok::getSinkHandler($sink->id)->importNewChunks();
        }
        return (new ChunkDispatcher($sink))
            ->setForceFetch((bool) $request->input('forceFetch'))
            ->setForceImport((bool) $request->input('forceImport'))
            ->{$operation}($this->getChunkIdsFromRequest($request, $sink));
    }

    /**
     * Get a list of chunk IDs based on input filters.
     *
     * @return array
     */
    protected function getChunkIdsFromRequest(Request $request, Sink $sink): array
    {
        $query = $this->applyFilters($request->input(), $sink->chunks());
        return $this->applyTargetFilters($request, $query)->get()->pluck('id')->toArray();
    }

    /**
     * @param Builder $query
     *
     * @return Builder
     */
    protected function applyTargetFilters(Request $request, $query): Builder
    {
        if ($request->input('targetSet') === 'selection') {
            $query->whereIn('id', $request->input('selection'));
        }
        return $query;
    }
}
