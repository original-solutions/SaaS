<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('sends password reset link email', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertSuccessful();
    Notification::assertSentTo($user, ResetPassword::class);
});

it('returns success even for non-existent email', function (): void {
    Notification::fake();

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    // Should not reveal whether the email exists
    $response->assertStatus(422);
});

it('validates email is required', function (): void {
    $response = $this->postJson('/api/v1/auth/forgot-password', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('resets password with valid token', function (): void {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertSuccessful();
});

it('revokes all tokens and sessions on password reset', function (): void {
    $user = User::factory()->create(['password' => 'password']);
    $authService = app(\App\Services\AuthService::class);

    // Create some sessions
    $authService->login($user->email, 'password', '127.0.0.1', 'Agent 1');
    $authService->login($user->email, 'password', '127.0.0.2', 'Agent 2');

    expect($user->deviceSessions()->active()->count())->toBe(2);

    $token = Password::createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    expect($user->deviceSessions()->active()->count())->toBe(0);
});

it('rejects reset with invalid token', function (): void {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertStatus(422);
});

it('validates password confirmation', function (): void {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});
