<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request, BookingService $bookingService)
    {
        $bookings = $bookingService->listForUser($request->user());

        return BookingResource::collection($bookings);
    }

    public function store(StoreBookingRequest $request, BookingService $bookingService)
    {
        $booking = $bookingService->create($request->user(), $request->validated());

        return BookingResource::make($booking)->response()->setStatusCode(201);
    }
}
