<?php

use App\Models\Room;
use App\Models\User;
use App\Http\Resources\Room\RoomResource;
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

Broadcast::channel('rooms.{roomId}', function (User $user, int $roomId) {
    // $room = Room::find($roomId);
    if (in_array($user->id, explode('-', Room::find($roomId)->users))) {
        return [
            'avatar' => $user->avatar,
            'created_at' => $user->created_at,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'id' => $user->id,
            'is_blocked' => $user->is_blocked,
            'is_online' => $user->is_online,
            'name' => $user->name,
            'updated_at' => $user->updated_at,
            'wallet' => $user->wallet
        ];
    } else {
        return false;
    }
});
