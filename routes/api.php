<?php

use App\Http\Controllers\ChunkController;
use App\Http\Controllers\SinkApiController;
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

Route::resource('sink', SinkApiController::class)->only(['index', 'show', 'update']);

Route::controller(ChunkController::class)->group(function () {
    Route::get('sink/{sink}/chunk', 'index');
    Route::post('sink/{sink}/chunk/fetch', 'fetch');
    Route::post('sink/{sink}/chunk/deleteFetched', 'deleteFetched');
    Route::post('sink/{sink}/chunk/import', 'import');
    Route::post('sink/{sink}/chunk/deleteImported', 'deleteImported');
});
