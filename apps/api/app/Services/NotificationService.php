<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Cohost;
use App\Models\Message;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\BookingRequestedNotification;
use App\Notifications\BookingStatusChangedNotification;
use App\Notifications\MessageReceivedNotification;
use App\Notifications\PaymentCapturedNotification;
use App\Notifications\PaymentRefundedNotification;
use App\Notifications\WelcomeNotification;

class NotificationService
{
    public function listForUser(User $user, int $perPage = 15)
    {
        return $user->notifications()->latest()->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markRead(User $user, string $notificationId): void
    {
        $notification = $user->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();
    }

    public function markAllRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    public function notifyMessageReceived(Message $message): void
    {
        $message->loadMissing(['conversation.listing', 'conversation.host', 'conversation.guest', 'sender']);
        $conversation = $message->conversation;
        if (! $conversation) {
            return;
        }

        $sender = $message->sender;
        $recipients = collect([$conversation->host, $conversation->guest]);

        $cohostIds = Cohost::query()
            ->where('listing_id', $conversation->listing_id)
            ->where('can_read_conversations', true)
            ->pluck('cohost_user_id');

        if ($cohostIds->isNotEmpty()) {
            $recipients = $recipients->merge(User::query()->whereIn('id', $cohostIds)->get());
        }

        $recipients
            ->filter()
            ->unique('id')
            ->reject(fn (User $user) => $sender && $user->id === $sender->id)
            ->each(function (User $recipient) use ($conversation, $sender, $message) {
                $recipient->notify(new MessageReceivedNotification(
                    $message,
                    $conversation->host_user_id === $recipient->id,
                    $sender?->name ?? 'Nouveau message'
                ));
            });
    }

    public function notifyWelcome(User $user): void
    {
        $user->notify(new WelcomeNotification($user->name ?? ''));
    }

    public function notifyBookingRequested(Booking $booking): void
    {
        $booking->loadMissing(['listing', 'guest']);
        $listing = $booking->listing;

        if (! $listing) {
            return;
        }

        $listing->loadMissing('host');
        $recipients = collect([$listing->host]);

        $cohostIds = Cohost::query()
            ->where('listing_id', $listing->id)
            ->where('can_edit_listings', true)
            ->pluck('cohost_user_id');

        if ($cohostIds->isNotEmpty()) {
            $recipients = $recipients->merge(User::query()->whereIn('id', $cohostIds)->get());
        }

        $recipients
            ->filter()
            ->unique('id')
            ->each(fn (User $recipient) => $recipient->notify(new BookingRequestedNotification($booking)));
    }

    public function notifyBookingStatusForGuest(Booking $booking): void
    {
        $booking->loadMissing(['listing', 'guest']);
        $guest = $booking->guest;

        if (! $guest) {
            return;
        }

        $guest->notify(new BookingStatusChangedNotification($booking, false));
    }

    public function notifyBookingStatusForHost(Booking $booking): void
    {
        $booking->loadMissing(['listing', 'guest']);
        $listing = $booking->listing;

        if (! $listing) {
            return;
        }

        $listing->loadMissing('host');
        $recipients = collect([$listing->host]);

        $cohostIds = Cohost::query()
            ->where('listing_id', $listing->id)
            ->where('can_edit_listings', true)
            ->pluck('cohost_user_id');

        if ($cohostIds->isNotEmpty()) {
            $recipients = $recipients->merge(User::query()->whereIn('id', $cohostIds)->get());
        }

        $recipients
            ->filter()
            ->unique('id')
            ->each(fn (User $recipient) => $recipient->notify(new BookingStatusChangedNotification($booking, true)));
    }

    public function notifyPaymentCaptured(Payment $payment): void
    {
        $payment->loadMissing(['booking.listing.host', 'booking.guest']);
        $booking = $payment->booking;
        $listing = $booking?->listing;
        $guest = $booking?->guest;

        if (! $listing || ! $guest) {
            return;
        }

        $recipients = collect([$guest, $listing->host]);

        $cohostIds = Cohost::query()
            ->where('listing_id', $listing->id)
            ->where('can_edit_listings', true)
            ->pluck('cohost_user_id');

        if ($cohostIds->isNotEmpty()) {
            $recipients = $recipients->merge(User::query()->whereIn('id', $cohostIds)->get());
        }

        $recipients
            ->filter()
            ->unique('id')
            ->each(function (User $recipient) use ($payment, $guest) {
                $isHostRecipient = $recipient->id !== $guest->id;
                $recipient->notify(new PaymentCapturedNotification($payment, $isHostRecipient));
            });
    }

    public function notifyPaymentRefunded(Payment $payment): void
    {
        $payment->loadMissing(['booking.listing.host', 'booking.guest']);
        $booking = $payment->booking;
        $listing = $booking?->listing;
        $guest = $booking?->guest;

        if (! $listing || ! $guest) {
            return;
        }

        $recipients = collect([$guest, $listing->host]);

        $cohostIds = Cohost::query()
            ->where('listing_id', $listing->id)
            ->where('can_edit_listings', true)
            ->pluck('cohost_user_id');

        if ($cohostIds->isNotEmpty()) {
            $recipients = $recipients->merge(User::query()->whereIn('id', $cohostIds)->get());
        }

        $recipients
            ->filter()
            ->unique('id')
            ->each(function (User $recipient) use ($payment, $guest) {
                $isHostRecipient = $recipient->id !== $guest->id;
                $recipient->notify(new PaymentRefundedNotification($payment, $isHostRecipient));
            });
    }
}
