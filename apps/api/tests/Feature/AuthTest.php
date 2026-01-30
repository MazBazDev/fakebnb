<?php

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('registers a user and returns a token', function () {
    $payload = [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password123',
    ];

    $response = $this->postJson('/api/v1/auth/register', $payload);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

    $this->assertDatabaseHas('users', [
        'email' => 'ada@example.com',
    ]);
});

it('logs in a user and returns a token', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
        ->assertJson(['message' => 'Identifiants invalides.']);
});

it('returns the current user when authenticated', function () {
    $user = User::factory()->create();
    $token = ApiToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', 'plain-token'),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer plain-token')
        ->getJson('/api/v1/me');

    $response->assertOk()
        ->assertJson([
            'id' => $user->id,
            'email' => $user->email,
        ]);
});

it('revokes the token on logout', function () {
    $user = User::factory()->create();
    ApiToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', 'logout-token'),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer logout-token')
        ->postJson('/api/v1/auth/logout');

    $response->assertOk()
        ->assertJson(['message' => 'Déconnecté.']);

    $this->assertDatabaseMissing('api_tokens', [
        'token_hash' => hash('sha256', 'logout-token'),
    ]);
});

it('promotes a user to host role', function () {
    $user = User::factory()->create();
    ApiToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', 'host-token'),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer host-token')
        ->postJson('/api/v1/me/host');

    $response->assertOk()
        ->assertJson(['message' => 'Rôle hôte activé.']);

    $this->assertDatabaseHas('roles', [
        'name' => 'host',
    ]);

    $hostRoleId = \App\Models\Role::where('name', 'host')->value('id');

    $this->assertDatabaseHas('role_user', [
        'user_id' => $user->id,
        'role_id' => $hostRoleId,
    ]);
});
