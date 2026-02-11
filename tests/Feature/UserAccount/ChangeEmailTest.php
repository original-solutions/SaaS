<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('changes user email and triggers re-verification', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/account/email', [
            'email' => 'new@example.com',
            'password' => 'password',
        ])
        ->assertSuccessful();

    $user->refresh();
    expect($user->email)->toBe('new@example.com');
    expect($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('requires valid password for email change', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/account/email', [
            'email' => 'new@example.com',
            'password' => 'wrong-password',
        ])
        ->assertUnprocessable();
});

it('requires unique email', function () {
    $existing = User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/account/email', [
            'email' => 'taken@example.com',
            'password' => 'password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
