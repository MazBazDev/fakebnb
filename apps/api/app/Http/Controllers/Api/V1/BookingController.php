<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Bookings', 'Réservations côté client et hôte')]
class BookingController extends Controller
{
    /**
     * Liste des réservations de l'utilisateur courant.
     */
    public function index(Request $request, BookingService $bookingService)
    {
        $bookings = $bookingService->listForUser($request->user());

        return BookingResource::collection($bookings);
    }

    /**
     * Créer une réservation.
     */
    public function store(StoreBookingRequest $request, BookingService $bookingService)
    {
        $booking = $bookingService->create($request->user(), $request->validated());

        return BookingResource::make($booking)->response()->setStatusCode(201);
    }

    /**
     * Confirmer une réservation (hôte).
     */
    public function confirm(Request $request, Booking $booking, BookingService $bookingService)
    {
        $booking = $bookingService->confirm($request->user(), $booking->loadMissing('listing'));

        return BookingResource::make($booking);
    }

    /**
     * Rejeter une réservation (hôte).
     */
    public function reject(Request $request, Booking $booking, BookingService $bookingService)
    {
        $booking = $bookingService->reject($request->user(), $booking->loadMissing('listing'));

        return BookingResource::make($booking);
    }

    /**
     * Annuler une réservation.
     */
    public function cancel(Request $request, Booking $booking, BookingService $bookingService)
    {
        $booking = $bookingService->cancel($request->user(), $booking->loadMissing('payment'));

        return BookingResource::make($booking);
    }
}
