<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if ($conversation->host_user_id === $user->id || $conversation->guest_user_id === $user->id) {
            return true;
        }

        return $user->cohostedBy()
            ->where('listing_id', $conversation->listing_id)
            ->where('can_read_conversations', true)
            ->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }
}
