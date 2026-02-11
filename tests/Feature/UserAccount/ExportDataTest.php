<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('exports user data including profile and memberships', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/account/export')
        ->assertSuccessful();

    $data = $response->json('data');

    expect($data)->toHaveKey('profile')
        ->toHaveKey('memberships')
        ->toHaveKey('activity_log');
    expect($data['profile']['email'])->toBe($user->email);
});
