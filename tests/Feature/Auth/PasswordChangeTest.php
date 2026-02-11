<?php

use App\Models\User;
use App\Services\AuthService;

it('changes password successfully', function (): void {
    $user = User::factory()->create(['password' => 'old-password']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/account/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

    $response->assertSuccessful()
        ->assertJson(['message' => 'Password changed. All sessions revoked.']);
});

it('revokes all sessions on password change', function (): void {
    $user = User::factory()->create(['password' => 'old-password']);
    $authService = app(AuthService::class);

    // Create device sessions
    $authService->login($user->email, 'old-password', '127.0.0.1', 'Agent 1');
    $authService->login($user->email, 'old-password', '127.0.0.2', 'Agent 2');

    expect($user->deviceSessions()->active()->count())->toBe(2);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/account/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

    expect($user->deviceSessions()->active()->count())->toBe(0);
});

it('rejects wrong current password', function (): void {
    $user = User::factory()->create(['password' => 'old-password']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/account/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

    $response->assertUnprocessable();
});

it('validates required fields', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/account/password', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password', 'password']);
});

it('requires authentication', function (): void {
    $response = $this->putJson('/api/v1/account/password', [
        'current_password' => 'old-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertUnauthorized();
});
