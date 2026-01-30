<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
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
            ->with('listing')
            ->where('guest_user_id', $user->id)
            ->orWhereIn('listing_id', $hostListingIds)
            ->orWhereIn('listing_id', $cohostListingIds)
            ->latest()
            ->get();
    }

    public function create(User $guest, array $data): Booking
    {
        Gate::authorize('create', Booking::class);

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();

        $hasConflict = Booking::query()
            ->where('listing_id', $data['listing_id'])
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
        ]);
    }
}
