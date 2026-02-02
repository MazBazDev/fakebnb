<?php

namespace App\Http\Controllers\Api\V1;

use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('System', 'Vérifications de santé et ping', 0)]
class StatusController
{
    /**
     * Health check.
     *
     * @unauthenticated
     */
    public function health()
    {
        return response()->json(['status' => 'ok']);
    }

    /**
     * Ping avec timestamp.
     *
     * @unauthenticated
     */
    public function ping(Request $request)
    {
        return response()->json([
            'message' => 'ok',
            'time' => now()->toIso8601String(),
        ]);
    }
}
