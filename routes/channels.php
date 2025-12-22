<?php

use App\Models\Conversation;
use App\Models\Shift;
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

// User-specific private channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Shift-specific channels
Broadcast::channel('shift.{shiftId}', function (User $user, $shiftId) {
    try {
        $shift = Shift::find($shiftId);
        if (! $shift) {
            return false;
        }

        // Business owner, assigned workers, or admin can listen
        if ($user->isAdmin()) {
            return true;
        }

        if ($shift->business_id === $user->id) {
            return ['id' => $user->id, 'name' => $user->name, 'role' => 'business'];
        }

        if ($shift->assignments()->where('worker_id', $user->id)->exists()) {
            return ['id' => $user->id, 'name' => $user->name, 'role' => 'worker'];
        }

        return false;
    } catch (\Exception $e) {
        return false;
    }
});

// Conversation/messaging channels
Broadcast::channel('conversation.{conversationId}', function (User $user, $conversationId) {
    try {
        $conversation = Conversation::find($conversationId);
        if (! $conversation) {
            return false;
        }

        // Check if user is a participant
        return $conversation->participants()->where('user_id', $user->id)->exists()
            ? ['id' => $user->id, 'name' => $user->name]
            : false;
    } catch (\Exception $e) {
        return false;
    }
});

// Worker availability broadcasts (public channel for businesses)
Broadcast::channel('availability-broadcasts', function (User $user) {
    // Only businesses and agencies can listen to availability broadcasts
    return $user->isBusiness() || $user->isAgency();
});

// Presence channel for online users
Broadcast::channel('online-users', function (User $user) {
    try {
        return ['id' => $user->id, 'name' => $user->name, 'user_type' => $user->user_type ?? null];
    } catch (\Exception $e) {
        return false;
    }
});

// Worker-specific private channel (legacy support)
Broadcast::channel('worker.{workerId}', function ($user, $workerId) {
    return (int) $user->id === (int) $workerId;
});

// User-specific private channel (legacy support for user.{id} pattern)
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
