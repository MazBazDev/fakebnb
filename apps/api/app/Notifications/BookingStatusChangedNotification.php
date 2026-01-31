<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;

class BookingStatusChangedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        private Booking $booking,
        private bool $isHostRecipient
    ) {
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
        $listing = $this->booking->listing;
        $statusLabel = $this->statusLabel($this->booking->status);

        return (new MailMessage())
            ->subject('Statut de réservation mis à jour')
            ->line('Statut : ' . $statusLabel)
            ->line('Logement : ' . ($listing?->title ?? 'Votre réservation'))
            ->line(sprintf(
                'Dates : %s → %s',
                optional($this->booking->start_date)->format('d/m/Y'),
                optional($this->booking->end_date)->format('d/m/Y')
            ));
    }

    private function payload(): array
    {
        $listing = $this->booking->listing;
        $guest = $this->booking->guest;
        $statusLabel = $this->statusLabel($this->booking->status);

        return [
            'type' => 'booking_status_changed',
            'title' => 'Statut de réservation mis à jour',
            'body' => sprintf(
                'Réservation %s pour %s (%s → %s).',
                $statusLabel,
                $listing?->title ?? 'le logement',
                optional($this->booking->start_date)->format('d/m/Y'),
                optional($this->booking->end_date)->format('d/m/Y')
            ),
            'action_url' => $this->isHostRecipient ? '/host/bookings' : '/bookings',
            'booking_id' => $this->booking->id,
            'listing_id' => $listing?->id,
            'guest_id' => $guest?->id,
            'status' => $this->booking->status,
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'en attente',
            'awaiting_payment' => 'en attente de paiement',
            'confirmed' => 'confirmée',
            'rejected' => 'refusée',
            'cancelled' => 'annulée',
            'completed' => 'terminée',
            default => $status,
        };
    }
}
