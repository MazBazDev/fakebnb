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
        $token = $user->token();
        if (! $token) {
            return;
        }

        $tokenId = $token->id;
        $token->revoke();

        DB::table('oauth_refresh_tokens')
            ->where('access_token_id', $tokenId)
            ->update(['revoked' => true]);
    }
}
