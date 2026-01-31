<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageCreated;
use Illuminate\Support\Facades\Gate;

class MessageService
{
    public function listForConversation(User $user, Conversation $conversation)
    {
        Gate::authorize('view', $conversation);

        return $conversation->messages()->with('sender')->latest()->get();
    }

    public function create(User $user, Conversation $conversation, string $body): Message
    {
        Gate::authorize('create', [Message::class, $conversation]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_user_id' => $user->id,
            'body' => $body,
        ]);

        $message->loadMissing('sender');
        event(new MessageCreated($message));

        return $message;
    }
}
