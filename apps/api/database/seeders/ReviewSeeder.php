<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bookings = Booking::query()
            ->where('status', 'confirmed')
            ->whereDate('end_date', '<', Carbon::today())
            ->whereDoesntHave('review')
            ->limit(10)
            ->get();

        foreach ($bookings as $booking) {
            Review::factory()->create([
                'booking_id' => $booking->id,
                'listing_id' => $booking->listing_id,
                'guest_user_id' => $booking->guest_user_id,
            ]);
        }
    }
}
