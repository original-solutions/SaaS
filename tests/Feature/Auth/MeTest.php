<?php

use App\Models\User;

it('returns authenticated user profile', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me');

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.name', $user->name);
});

it('returns 401 for unauthenticated user', function (): void {
    $response = $this->getJson('/api/v1/me');

    $response->assertUnauthorized();
});

it('does not expose sensitive fields', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me');

    $data = $response->json('data');
    expect($data)->not->toHaveKey('password');
    expect($data)->not->toHaveKey('two_factor_secret');
    expect($data)->not->toHaveKey('two_factor_recovery_codes');
});
