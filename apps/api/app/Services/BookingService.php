<?php

namespace App\Services;

use App\Models\Booking;
use App\Events\BookingUpdated;
use App\Models\Cohost;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use App\Services\NotificationService;

class BookingService
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function listForUser(User $user)
    {
        $hostListingIds = Listing::query()
            ->where('host_user_id', $user->id)
            ->pluck('id');

        $cohostListingIds = \App\Models\Cohost::query()
            ->where('cohost_user_id', $user->id)
            ->pluck('listing_id');

        return Booking::query()
            ->with(['listing.images', 'guest', 'payment'])
            ->where('guest_user_id', $user->id)
            ->orWhereIn('listing_id', $hostListingIds)
            ->orWhereIn('listing_id', $cohostListingIds)
            ->latest()
            ->get();
    }

    public function findForUser(User $user, Booking $booking): Booking
    {
        return $booking->load(['listing.images', 'guest', 'payment', 'review.repliedBy', 'review.guest']);
    }

    public function listConfirmedForListing(Listing $listing)
    {
        return Booking::query()
            ->where('listing_id', $listing->id)
            ->where('status', 'confirmed')
            ->orderBy('start_date')
            ->get();
    }

    public function countActiveForGuest(User $user): int
    {
        $today = Carbon::today();

        return Booking::query()
            ->where('guest_user_id', $user->id)
            ->where('status', '!=', 'rejected')
            ->whereDate('end_date', '>=', $today)
            ->count();
    }

    public function create(User $guest, array $data): Booking
    {
        Gate::authorize('create', Booking::class);

        $listing = Listing::findOrFail($data['listing_id']);
        $isHost = $listing->host_user_id === $guest->id;
        $isCohost = Cohost::query()
            ->where('listing_id', $listing->id)
            ->where('cohost_user_id', $guest->id)
            ->exists();

        if ($isHost || $isCohost) {
            throw new AuthorizationException('Impossible de réserver votre propre annonce.');
        }

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();

        $hasConflict = Booking::query()
            ->where('listing_id', $data['listing_id'])
            ->where('status', 'confirmed')
            ->whereDate('start_date', '<', $end->toDateString())
            ->whereDate('end_date', '>', $start->toDateString())
            ->exists();

        if ($hasConflict) {
            throw new ConflictHttpException('Dates indisponibles.');
        }

        $booking = Booking::create([
            'listing_id' => $data['listing_id'],
            'guest_user_id' => $guest->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => 'pending',
        ]);

        BookingUpdated::dispatch($booking);
        $this->notificationService->notifyBookingRequested($booking);

        return $booking;
    }

    public function confirm(User $host, Booking $booking): Booking
    {
        Gate::authorize('confirm', $booking);

        if ($booking->status !== 'pending') {
            return $booking;
        }

        $hasConflict = Booking::query()
            ->where('listing_id', $booking->listing_id)
            ->where('status', 'confirmed')
            ->where('id', '!=', $booking->id)
            ->whereDate('start_date', '<', $booking->end_date->toDateString())
            ->whereDate('end_date', '>', $booking->start_date->toDateString())
            ->exists();

        if ($hasConflict) {
            throw new ConflictHttpException('Dates indisponibles.');
        }

        $booking->status = 'awaiting_payment';
        $booking->save();

        BookingUpdated::dispatch($booking);
        $this->notificationService->notifyBookingStatusForGuest($booking);

        return $booking->fresh();
    }

    public function reject(User $host, Booking $booking): Booking
    {
        Gate::authorize('reject', $booking);

        $booking->status = 'rejected';
        $booking->save();

        BookingUpdated::dispatch($booking);
        $this->notificationService->notifyBookingStatusForGuest($booking);

        return $booking->fresh();
    }

    public function cancel(User $guest, Booking $booking): Booking
    {
        Gate::authorize('cancel', $booking);

        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return $booking;
        }

        $booking->status = 'cancelled';
        $booking->save();

        $payment = $booking->payment;
        if ($payment && in_array($payment->status, ['authorized', 'captured'], true)) {
            $payment->status = 'refunded';
            $payment->refunded_at = now();
            $payment->save();
            $this->notificationService->notifyPaymentRefunded($payment);
        }

        BookingUpdated::dispatch($booking->fresh()->load('payment'));
        if ($guest->id === $booking->guest_user_id) {
            $this->notificationService->notifyBookingStatusForHost($booking);
        } else {
            $this->notificationService->notifyBookingStatusForGuest($booking);
        }

        return $booking->fresh()->load('payment');
    }
}
