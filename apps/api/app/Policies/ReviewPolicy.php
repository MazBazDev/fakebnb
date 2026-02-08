<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\Cohost;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Carbon;

class ReviewPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user = null): bool
    {
        return true;
    }

    public function view(?User $user = null, Review $review): bool
    {
        return true;
    }

    public function create(User $user, Booking $booking): bool
    {
        if ($booking->guest_user_id !== $user->id) {
            return false;
        }

        if (! in_array($booking->status, ['confirmed', 'completed'], true)) {
            return false;
        }

        if ($booking->end_date && $booking->end_date->toDateString() >= Carbon::today()->toDateString()) {
            return false;
        }

        return ! $booking->review()->exists();
    }

    public function reply(User $user, Review $review): bool
    {
        $listing = $review->listing()->first();
        if (! $listing) {
            return false;
        }

        if ($listing->host_user_id === $user->id) {
            return true;
        }

        return Cohost::query()
            ->where('listing_id', $listing->id)
            ->where('cohost_user_id', $user->id)
            ->where('can_reply_messages', true)
            ->exists();
    }
}
