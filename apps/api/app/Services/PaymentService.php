<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class PaymentService
{
    public function createIntent(Booking $booking, int $guestId): Payment
    {
        if ($booking->guest_user_id !== $guestId) {
            throw new AuthorizationException('Action interdite.');
        }

        if ($booking->status !== 'awaiting_payment') {
            throw new AuthorizationException('Paiement non autorisé pour cette réservation.');
        }

        $listing = $booking->listing()->firstOrFail();
        $nights = max(1, $booking->start_date->diffInDays($booking->end_date));
        $baseAmount = $nights * $listing->price_per_night;

        $vatRate = (float) config('payment.vat_rate', 0.20);
        $serviceRate = (float) config('payment.service_fee_rate', 0.07);
        $commissionRate = (float) config('payment.commission_rate', 0.12);

        $vatAmount = (int) round($baseAmount * $vatRate);
        $serviceAmount = (int) round($baseAmount * $serviceRate);
        $totalAmount = $baseAmount + $vatAmount + $serviceAmount;
        $commissionAmount = (int) round($baseAmount * $commissionRate);
        $payoutAmount = max(0, $baseAmount - $commissionAmount);

        return Payment::firstOrCreate(
            [
                'booking_id' => $booking->id,
                'guest_user_id' => $booking->guest_user_id,
            ],
            [
                'host_user_id' => $listing->host_user_id,
                'amount_total' => $totalAmount,
                'amount_base' => $baseAmount,
                'amount_vat' => $vatAmount,
                'amount_service' => $serviceAmount,
                'commission_amount' => $commissionAmount,
                'payout_amount' => $payoutAmount,
                'status' => 'requires_authorization',
            ]
        );
    }

    public function authorize(Payment $payment, int $guestId): Payment
    {
        Gate::authorize('authorize', $payment);

        if ($payment->guest_user_id !== $guestId) {
            throw new AuthorizationException('Action interdite.');
        }

        if (! in_array($payment->status, ['requires_authorization', 'failed'], true)) {
            return $payment;
        }

        $payment->status = 'authorized';
        $payment->authorized_at = now();
        $payment->save();

        return $this->capture($payment);
    }

    public function capture(Payment $payment): Payment
    {
        $payment->status = 'captured';
        $payment->captured_at = now();
        $payment->save();

        $booking = $payment->booking;
        $booking->status = 'confirmed';
        $booking->paid_at = now();
        $booking->save();

        return $payment->fresh();
    }
}
