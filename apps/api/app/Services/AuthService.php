<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class AuthService
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function register(array $data): array
    {
        $user = User::create($data);
        $this->notificationService->notifyWelcome($user);

        return [
            'user' => $user,
        ];
    }

    public function logout(User $user): void
    {
        $tokenIds = $user->tokens()->pluck('id');
        if ($tokenIds->isEmpty()) {
            return;
        }

        DB::table('oauth_access_tokens')
            ->whereIn('id', $tokenIds)
            ->update(['revoked' => true]);

        DB::table('oauth_refresh_tokens')
            ->whereIn('access_token_id', $tokenIds)
            ->update(['revoked' => true]);
    }
}
