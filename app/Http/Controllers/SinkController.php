<?php

namespace App\Http\Controllers;

use App\Http\Resources\SinkCollection;
use App\Http\Resources\SinkResource;
use App\Models\Sink;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class SinkController extends Controller
{
    public function __construct()
    {
        $this->middleware('sinks');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('ImportStatus', [
            'sinks' => (new SinkCollection(Sink::all()))->toArray($request),
            'permissions' => $this->propPermissions($request->user()),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Sink $sink)
    {
        return Inertia::render('SinkStatus', [
            'sink' => (new SinkResource($sink))->toArray($request),
            'permissions' => $this->propPermissions($request->user()),
        ]);
    }

    protected function propPermissions(User $user)
    {
        $operations = ['fetch', 'import', 'deleteFetched', 'deleteImported'];
        return [
            'readSinks' => $user->can('read sinks'),
            'readChunks' => $user->can('read chunks'),
            'deleteBatches' => $user->can('delete batches'),
            'operations' => array_reduce(
                $operations,
                function ($carry, $operation) use ($user) {
                    $carry[$operation] = $user->can("$operation chunks");
                    return $carry;
                },
                []
            ),
        ];
    }
}
