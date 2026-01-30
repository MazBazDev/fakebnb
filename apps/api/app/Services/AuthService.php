<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create($data);
        app(RoleService::class)->assignRole($user, 'client');
        $token = $this->issueToken($user);

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new AuthenticationException('Identifiants invalides.');
        }

        $token = $this->issueToken($user);

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function logout(?ApiToken $token): void
    {
        if ($token instanceof ApiToken) {
            $token->delete();
        }
    }

    private function issueToken(User $user): string
    {
        $plain = Str::random(64);

        ApiToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
        ]);

        return $plain;
    }
}
