<?php

use App\Http\Controllers\ChunkController;
use App\Http\Controllers\ImportController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::resource('sink', ImportController::class)->only(['store', 'show', 'update', 'destroy']);
Route::resource('sink/{sinkId}/chunk', ChunkController::class)->except(['destroy', 'store']);

Route::controller(ChunkController::class)->group(function () {
    Route::get('sink/{sinkId}/chunk', 'index');
    Route::post('sink/{sinkId}/chunk/fetch', 'fetch');
    Route::post('sink/{sinkId}/chunk/import', 'import');
    Route::post('sink/{sinkId}/chunk/destroy', 'destroy');
    Route::post('sink/{sinkId}/chunk/deleteImport', 'deleteImport');
});
