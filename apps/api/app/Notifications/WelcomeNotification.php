<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(private string $name)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Bienvenue sur Fakebnb')
            ->greeting('Bonjour ' . ($this->name ?: ''))
            ->line('Merci pour votre inscription.')
            ->line('Vous pouvez maintenant réserver des logements et contacter des hôtes.');
    }

    private function payload(): array
    {
        return [
            'type' => 'welcome',
            'title' => 'Bienvenue sur Fakebnb',
            'body' => 'Votre compte est prêt. Commencez votre première réservation.',
            'action_url' => '/',
        ];
    }
}
