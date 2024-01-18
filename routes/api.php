<?php

use App\Http\Controllers\BatchApiController;
use App\Http\Controllers\ChunkController;
use App\Http\Controllers\SinkApiController;
use App\Http\Controllers\SinkSchemaApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('sinks', SinkApiController::class)->only(['index', 'show', 'update']);
    Route::controller(SinkApiController::class)->group(function () {
        Route::get('sinks/{sink}/scan', 'scanLocalFiles');
    });

    Route::apiResource('sinks.schemas', SinkSchemaApiController::class)->only(['index', 'show']);

    Route::apiResource('sinks.chunks', ChunkController::class)->only(['index', 'update']);
    Route::controller(ChunkController::class)->group(function () {
        Route::get('sinks/{sink}/chunks/{chunk}/download', 'download');
    });

    Route::apiResource('batch', BatchApiController::class)->only(['index', 'show', 'destroy']);
});
