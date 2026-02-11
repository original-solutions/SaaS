<?php

use App\Models\DeviceSession;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\AuthService;

beforeEach(function (): void {
    $this->user = User::factory()->create(['password' => 'password']);
    $this->authService = app(AuthService::class);
    $this->tokens = $this->authService->login(
        $this->user->email,
        'password',
        '127.0.0.1',
        'Test Agent',
    );
});

it('rotates tokens on successful refresh', function (): void {
    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $this->tokens['refresh_token'],
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['access_token', 'refresh_token', 'expires_in']);

    // New tokens should be different
    expect($response->json('refresh_token'))->not->toBe($this->tokens['refresh_token']);
    expect($response->json('access_token'))->not->toBe($this->tokens['access_token']);
});

it('invalidates old refresh token after use', function (): void {
    $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $this->tokens['refresh_token'],
    ])->assertSuccessful();

    // Try to use the old refresh token again
    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $this->tokens['refresh_token'],
    ]);

    $response->assertUnauthorized();
});

it('rejects expired refresh token', function (): void {
    // Manually expire the refresh token
    $tokenHash = hash('sha256', $this->tokens['refresh_token']);
    RefreshToken::where('token_hash', $tokenHash)
        ->update(['expires_at' => now()->subMinute()]);

    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $this->tokens['refresh_token'],
    ]);

    $response->assertUnauthorized();
});

it('detects token reuse and revokes session chain', function (): void {
    // First: use the refresh token
    $firstRefresh = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $this->tokens['refresh_token'],
    ]);
    $firstRefresh->assertSuccessful();

    // Second: try to reuse the old token (simulates theft)
    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $this->tokens['refresh_token'],
    ]);
    $response->assertUnauthorized();

    // The device session should be revoked
    $deviceSession = DeviceSession::where('user_id', $this->user->id)->first();
    expect($deviceSession->isRevoked())->toBeTrue();
    expect($deviceSession->revocation_reason->value)->toBe('refresh_reuse_detected');
});

it('creates activity log on token reuse detection', function (): void {
    // Use token once
    $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $this->tokens['refresh_token'],
    ]);

    // Reuse (theft simulation)
    $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $this->tokens['refresh_token'],
    ]);

    $this->assertDatabaseHas('activity_log', [
        'causer_id' => $this->user->id,
        'event' => 'refresh_token_reuse_detected',
    ]);
});

it('revokes all sessions on reuse when config enabled', function (): void {
    config(['saas.revoke_all_on_reuse' => true]);

    // Create a second device session
    $tokens2 = $this->authService->login($this->user->email, 'password', '127.0.0.2', 'Agent 2');

    // Use and reuse the first token
    $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $this->tokens['refresh_token'],
    ]);
    $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $this->tokens['refresh_token'],
    ]);

    // All sessions should be revoked
    expect(DeviceSession::where('user_id', $this->user->id)->active()->count())->toBe(0);
});

it('validates refresh_token is required', function (): void {
    $response = $this->postJson('/api/v1/auth/refresh', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['refresh_token']);
});

it('returns new expires_in with refreshed token', function (): void {
    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $this->tokens['refresh_token'],
    ]);

    $ttlMinutes = (int) config('saas.access_token_ttl', 15);
    expect($response->json('expires_in'))->toBe($ttlMinutes * 60);
});
