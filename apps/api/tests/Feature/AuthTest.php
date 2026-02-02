<?php

use App\Models\User;
use Laravel\Passport\Passport;

it('registers a user', function () {
    $payload = [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password123',
    ];

    $response = $this->postJson('/api/v1/auth/register', $payload);

    $response->assertCreated()
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'ada@example.com',
    ]);
});

it('returns the current user when authenticated', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $response = $this->getJson('/api/v1/me');

    $response->assertOk()
        ->assertJson([
            'id' => $user->id,
            'email' => $user->email,
        ]);
});

it('revokes the token on logout', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertOk()
        ->assertJson(['message' => 'Déconnecté.']);
});
