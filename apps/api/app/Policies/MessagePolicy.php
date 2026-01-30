<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return app(ConversationPolicy::class)->view($user, $conversation);
    }

    public function create(User $user, Conversation $conversation): bool
    {
        if ($conversation->host_user_id === $user->id || $conversation->guest_user_id === $user->id) {
            return true;
        }

        return $user->cohostedBy()
            ->where('listing_id', $conversation->listing_id)
            ->where('can_reply_messages', true)
            ->exists();
    }
}
