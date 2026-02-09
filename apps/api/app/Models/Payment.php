<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'booking_id',
        'guest_user_id',
        'host_user_id',
        'amount_total',
        'amount_base',
        'amount_vat',
        'amount_service',
        'commission_amount',
        'payout_amount',
        'cashoula_direct_applied',
        'cashoula_direct_won',
        'status',
        'authorized_at',
        'captured_at',
        'refunded_at',
    ];

    protected $casts = [
        'authorized_at' => 'datetime',
        'captured_at' => 'datetime',
        'refunded_at' => 'datetime',
        'cashoula_direct_applied' => 'bool',
        'cashoula_direct_won' => 'bool',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_user_id');
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }
}
