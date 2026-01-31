<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Str;

class MessageReceivedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        private Message $message,
        private bool $isHostRecipient,
        private string $senderName
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    private function payload(): array
    {
        $conversation = $this->message->conversation;
        $listing = $conversation?->listing;

        return [
            'type' => 'message_received',
            'title' => 'Nouveau message',
            'body' => $this->senderName . ' : ' . Str::limit($this->message->body, 120),
            'action_url' => $this->isHostRecipient && $listing
                ? "/host/listings/{$listing->id}/messages"
                : '/messages',
            'conversation_id' => $this->message->conversation_id,
            'listing_id' => $listing?->id,
            'sender_id' => $this->message->sender_user_id,
            'sender_name' => $this->senderName,
        ];
    }
}
