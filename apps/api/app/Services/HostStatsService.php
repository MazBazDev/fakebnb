<?php

namespace App\Services;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;

class HostStatsService
{
    public function forUser(User $user): array
    {
        $listingIds = Listing::query()
            ->where('host_user_id', $user->id)
            ->pluck('id');

        $listingsCount = $listingIds->count();

        $bookingsQuery = Booking::query()
            ->whereIn('listing_id', $listingIds);

        $pendingCount = (clone $bookingsQuery)
            ->where('status', 'pending')
            ->count();

        $awaitingPaymentCount = (clone $bookingsQuery)
            ->where('status', 'awaiting_payment')
            ->count();

        $confirmedCount = (clone $bookingsQuery)
            ->where('status', 'confirmed')
            ->count();

        $totalPayout = Payment::query()
            ->whereIn('booking_id', (clone $bookingsQuery)->select('id'))
            ->where('status', 'captured')
            ->sum('payout_amount');

        $upcomingArrivals = (clone $bookingsQuery)
            ->where('status', 'confirmed')
            ->whereDate('start_date', '>=', now()->toDateString())
            ->with(['listing', 'guest', 'payment'])
            ->orderBy('start_date')
            ->limit(3)
            ->get();

        $recentRequests = (clone $bookingsQuery)
            ->with(['listing', 'guest', 'payment'])
            ->latest()
            ->limit(4)
            ->get();

        $series = $this->buildMonthlySeries($listingsCount ? $listingIds->all() : []);

        return [
            'listings_count' => $listingsCount,
            'pending_count' => $pendingCount,
            'awaiting_payment_count' => $awaitingPaymentCount,
            'confirmed_count' => $confirmedCount,
            'total_payout' => (int) $totalPayout,
            'upcoming_arrivals' => BookingResource::collection($upcomingArrivals)->resolve(),
            'recent_requests' => BookingResource::collection($recentRequests)->resolve(),
            'series' => $series,
        ];
    }

    private function buildMonthlySeries(array $listingIds): array
    {
        $months = [];
        $bookingCounts = [];
        $payoutTotals = [];

        $start = Carbon::now()->startOfMonth()->subMonths(5);

        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $months[] = $month->format('Y-m');

            $bookingCounts[] = Booking::query()
                ->whereIn('listing_id', $listingIds)
                ->where('status', 'confirmed')
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->count();

            $payoutTotals[] = (int) Payment::query()
                ->whereBetween('captured_at', [$monthStart, $monthEnd])
                ->where('status', 'captured')
                ->whereIn('booking_id', function ($query) use ($listingIds) {
                    $query->select('id')
                        ->from('bookings')
                        ->whereIn('listing_id', $listingIds);
                })
                ->sum('payout_amount');
        }

        return [
            'months' => $months,
            'booking_counts' => $bookingCounts,
            'payout_totals' => $payoutTotals,
        ];
    }
}
