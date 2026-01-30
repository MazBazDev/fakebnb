<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'guest_user_id' => $this->guest_user_id,
            'host_user_id' => $this->host_user_id,
            'amount_total' => $this->amount_total,
            'amount_base' => $this->amount_base,
            'amount_vat' => $this->amount_vat,
            'amount_service' => $this->amount_service,
            'commission_amount' => $this->commission_amount,
            'payout_amount' => $this->payout_amount,
            'status' => $this->status,
            'authorized_at' => $this->authorized_at?->toIso8601String(),
            'captured_at' => $this->captured_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
