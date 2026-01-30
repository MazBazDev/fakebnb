<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\Cohost;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $booking->guest_user_id === $user->id;
    }

    public function confirm(User $user, Booking $booking): bool
    {
        return $this->canManageBooking($user, $booking);
    }

    public function reject(User $user, Booking $booking): bool
    {
        return $this->canManageBooking($user, $booking);
    }

    private function canManageBooking(User $user, Booking $booking): bool
    {
        if ($booking->listing?->host_user_id === $user->id) {
            return true;
        }

        return Cohost::query()
            ->where('listing_id', $booking->listing_id)
            ->where('cohost_user_id', $user->id)
            ->where('can_edit_listings', true)
            ->exists();
    }
}
