<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Payments', 'Paiements (fake) et autorisations')]
class PaymentController extends Controller
{
    /**
     * Créer un intent de paiement.
     */
    public function intent(Request $request, PaymentService $paymentService)
    {
        $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
        ]);

        $booking = Booking::with('listing')->findOrFail($request->integer('booking_id'));
        $payment = $paymentService->createIntent($booking, $request->user()->id);

        return PaymentResource::make($payment)->response()->setStatusCode(201);
    }

    /**
     * Autoriser un paiement.
     */
    public function authorizePayment(Request $request, Payment $payment, PaymentService $paymentService)
    {
        $payment = $paymentService->authorize($payment, $request->user()->id);

        return PaymentResource::make($payment);
    }
}
