<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function authorize(User $user, Payment $payment): bool
    {
        return $payment->guest_user_id === $user->id;
    }
}
