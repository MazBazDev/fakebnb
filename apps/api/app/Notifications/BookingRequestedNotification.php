<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;

class BookingRequestedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(private Booking $booking)
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
        $listing = $this->booking->listing;
        $guest = $this->booking->guest;

        return (new MailMessage())
            ->subject('Nouvelle réservation')
            ->line(($guest?->name ?? 'Un voyageur') . ' souhaite réserver votre logement.')
            ->line('Logement : ' . ($listing?->title ?? 'Votre annonce'))
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

        return [
            'type' => 'booking_requested',
            'title' => 'Nouvelle réservation',
            'body' => sprintf(
                '%s souhaite réserver %s (%s → %s).',
                $guest?->name ?? 'Un voyageur',
                $listing?->title ?? 'votre annonce',
                optional($this->booking->start_date)->format('d/m/Y'),
                optional($this->booking->end_date)->format('d/m/Y')
            ),
            'action_url' => '/host/bookings',
            'booking_id' => $this->booking->id,
            'listing_id' => $listing?->id,
            'guest_id' => $guest?->id,
            'status' => $this->booking->status,
        ];
    }
}
