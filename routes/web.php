<?php

use App\Facades\Ragnarok;
use App\Http\Controllers\SinkController;
use App\Http\Controllers\UserAccountController;
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
    Route::resource('account', UserAccountController::class)->except(['create', 'edit']);
    Route::resource('sink', SinkController::class)->only(['index', 'show']);
    Route::get('/', [SinkController::class, 'index'])->name('home');

    Route::get('user.accounts', function () {
        return Inertia::render('AccountManagement/UserAccounts');
    })->name('user.accounts');
});
