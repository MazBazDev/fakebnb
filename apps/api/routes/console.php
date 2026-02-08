<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Carbon;
use App\Models\Booking;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Booking::query()
        ->where('status', 'confirmed')
        ->whereDate('end_date', '<', Carbon::today())
        ->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
})->everyMinute()->name('complete-past-bookings');
