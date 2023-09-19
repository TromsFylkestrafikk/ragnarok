<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('App.Models.SinkImport', function ($user) {
    // return $user->hasDirectPermission('read sources');
    return true;
});

Broadcast::channel('App.Models.Chunk', function ($user) {
    // return $user->hasDirectPermission('read sources');
    return true;
});

Broadcast::channel('sinks', function (User $user) {
    return $user->can('read sources');
});
