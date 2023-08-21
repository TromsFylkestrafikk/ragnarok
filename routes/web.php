<?php

use App\Facades\Ragnarok;
use App\Http\Controllers\SinkController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::resource('sink', SinkController::class)->only(['index', 'show', 'update']);
    Route::get('/', function () {
        return Inertia::render('ImportStatus', [
            'sinks' => Ragnarok::getSinksJson(),
        ]);
    })->name('home');

    Route::get('auth.roles', function () {
        return Inertia::render('Auth/Roles');
    })->name('user.roles');
});
