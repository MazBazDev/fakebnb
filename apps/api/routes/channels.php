<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:api']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    if ((int) $user->id === (int) $conversation->host_user_id) {
        return true;
    }

    if ((int) $user->id === (int) $conversation->guest_user_id) {
        return true;
    }

    return $user->cohostedBy()
        ->where('listing_id', $conversation->listing_id)
        ->where('can_read_conversations', true)
        ->exists();
});
