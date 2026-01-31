<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentRefundedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        private Payment $payment,
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
        $booking = $this->payment->booking;
        $listing = $booking?->listing;
        $total = $this->formatAmount($this->payment->amount_total);

        return (new MailMessage())
            ->subject('Remboursement effectué')
            ->line($this->isHostRecipient
                ? 'Le paiement a été remboursé.'
                : 'Votre remboursement a été effectué.')
            ->line('Logement : ' . ($listing?->title ?? 'Votre réservation'))
            ->line('Total : ' . $total);
    }

    private function payload(): array
    {
        $booking = $this->payment->booking;
        $listing = $booking?->listing;
        $guest = $booking?->guest;

        return [
            'type' => 'payment_refunded',
            'title' => 'Remboursement effectué',
            'body' => $this->isHostRecipient
                ? 'Paiement remboursé pour ' . ($listing?->title ?? 'une réservation') . '.'
                : 'Votre remboursement est confirmé pour ' . ($listing?->title ?? 'votre réservation') . '.',
            'action_url' => $this->isHostRecipient ? '/host/bookings' : '/bookings',
            'payment_id' => $this->payment->id,
            'booking_id' => $booking?->id,
            'listing_id' => $listing?->id,
            'guest_id' => $guest?->id,
            'status' => $this->payment->status,
            'amount_total' => $this->payment->amount_total,
        ];
    }

    private function formatAmount(int $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' €';
    }
}
