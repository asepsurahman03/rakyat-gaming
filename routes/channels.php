<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ChatRoom;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    $room = ChatRoom::find($roomId);

    if (!$room) {
        return false;
    }

    // Allow access if user is the room owner or is an admin
    return $user->id === $room->user_id || $user->role === 'admin';
});

Broadcast::channel('admin-chat', function ($user) {
    return $user->role === 'admin';
});
