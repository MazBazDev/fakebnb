<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ReviewResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'listing_id' => $this->listing_id,
            'guest_user_id' => $this->guest_user_id,
            'guest' => $this->whenLoaded('guest', function () {
                return [
                    'id' => $this->guest->id,
                    'name' => $this->guest->name,
                    'profile_photo_url' => $this->guest->profile_photo_url,
                ];
            }),
            'listing' => $this->whenLoaded('listing', function () {
                return [
                    'id' => $this->listing->id,
                    'title' => $this->listing->title,
                    'city' => $this->listing->city,
                    'cashoula_direct_enabled' => (bool) $this->listing->cashoula_direct_enabled,
                    'images' => $this->listing->relationLoaded('images')
                        ? $this->listing->images->map(function ($image) {
                            return [
                                'id' => $image->id,
                                'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($image->path),
                                'position' => $image->position,
                            ];
                        })->values()
                        : [],
                ];
            }),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'payment' => $this->whenLoaded('payment', function () {
                return [
                    'id' => $this->payment->id,
                    'status' => $this->payment->status,
                    'amount_total' => $this->payment->amount_total,
                    'amount_base' => $this->payment->amount_base,
                    'amount_vat' => $this->payment->amount_vat,
                    'amount_service' => $this->payment->amount_service,
                    'commission_amount' => $this->payment->commission_amount,
                    'payout_amount' => $this->payment->payout_amount,
                    'cashoula_direct_applied' => (bool) $this->payment->cashoula_direct_applied,
                    'cashoula_direct_won' => (bool) $this->payment->cashoula_direct_won,
                ];
            }),
            'review' => $this->whenLoaded('review', function () {
                return ReviewResource::make($this->review);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
