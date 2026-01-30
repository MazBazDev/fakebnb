<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Cohost;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class BookingService
{
    public function listForUser(User $user)
    {
        $hostListingIds = Listing::query()
            ->where('host_user_id', $user->id)
            ->pluck('id');

        $cohostListingIds = \App\Models\Cohost::query()
            ->where('cohost_user_id', $user->id)
            ->pluck('listing_id');

        return Booking::query()
            ->with(['listing', 'guest', 'payment'])
            ->where('guest_user_id', $user->id)
            ->orWhereIn('listing_id', $hostListingIds)
            ->orWhereIn('listing_id', $cohostListingIds)
            ->latest()
            ->get();
    }

    public function listConfirmedForListing(Listing $listing)
    {
        return Booking::query()
            ->where('listing_id', $listing->id)
            ->where('status', 'confirmed')
            ->orderBy('start_date')
            ->get();
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
            ->where('start_date', '<', $end->toDateString())
            ->where('end_date', '>', $start->toDateString())
            ->exists();

        if ($hasConflict) {
            throw new ConflictHttpException('Dates indisponibles.');
        }

        return Booking::create([
            'listing_id' => $data['listing_id'],
            'guest_user_id' => $guest->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => 'pending',
        ]);
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
            ->where('start_date', '<', $booking->end_date->toDateString())
            ->where('end_date', '>', $booking->start_date->toDateString())
            ->exists();

        if ($hasConflict) {
            throw new ConflictHttpException('Dates indisponibles.');
        }

        $booking->status = 'awaiting_payment';
        $booking->save();

        return $booking->fresh();
    }

    public function reject(User $host, Booking $booking): Booking
    {
        Gate::authorize('reject', $booking);

        $booking->status = 'rejected';
        $booking->save();

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
        }

        return $booking->fresh()->load('payment');
    }
}
