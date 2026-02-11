<?php

use App\Enums\RevocationReason;
use App\Models\DeviceSession;
use App\Models\User;
use App\Services\AuthService;

beforeEach(function (): void {
    $this->user = User::factory()->create(['password' => 'password']);
    $this->authService = app(AuthService::class);
});

it('login returns token pair with correct structure', function (): void {
    $result = $this->authService->login($this->user->email, 'password', '127.0.0.1', 'Agent');

    expect($result)->not->toBeNull()
        ->toHaveKeys(['access_token', 'refresh_token', 'expires_in']);
});

it('login returns null for invalid credentials', function (): void {
    $result = $this->authService->login($this->user->email, 'wrong', '127.0.0.1', 'Agent');

    expect($result)->toBeNull();
});

it('login returns error for locked user', function (): void {
    $locked = User::factory()->locked()->create(['password' => 'password']);

    $result = $this->authService->login($locked->email, 'password', '127.0.0.1', 'Agent');

    expect($result)->toBe(['error' => 'ACCOUNT_LOCKED']);
});

it('refresh returns new token pair', function (): void {
    $tokens = $this->authService->login($this->user->email, 'password', '127.0.0.1', 'Agent');
    $newTokens = $this->authService->refresh($tokens['refresh_token']);

    expect($newTokens)->not->toBeNull()
        ->toHaveKeys(['access_token', 'refresh_token', 'expires_in']);
    expect($newTokens['refresh_token'])->not->toBe($tokens['refresh_token']);
});

it('reuse detection revokes the session chain', function (): void {
    config(['saas.revoke_all_on_reuse' => false]);

    $tokens = $this->authService->login($this->user->email, 'password', '127.0.0.1', 'Agent');

    // First use succeeds
    $this->authService->refresh($tokens['refresh_token']);

    // Second use triggers reuse detection
    $result = $this->authService->refresh($tokens['refresh_token']);

    expect($result)->toBeNull();

    // Session should be revoked
    $session = DeviceSession::where('user_id', $this->user->id)->first();
    expect($session->revocation_reason)->toBe(RevocationReason::RefreshReuseDetected);
});

it('reuse detection revokes all sessions when config enabled', function (): void {
    config(['saas.revoke_all_on_reuse' => true]);

    // Create two sessions
    $tokens1 = $this->authService->login($this->user->email, 'password', '127.0.0.1', 'Agent 1');
    $tokens2 = $this->authService->login($this->user->email, 'password', '127.0.0.2', 'Agent 2');

    // Use and reuse token 1
    $this->authService->refresh($tokens1['refresh_token']);
    $this->authService->refresh($tokens1['refresh_token']);

    expect(DeviceSession::where('user_id', $this->user->id)->active()->count())->toBe(0);
});

it('revokeAllForUser revokes all sessions', function (): void {
    $this->authService->login($this->user->email, 'password', '127.0.0.1', 'Agent 1');
    $this->authService->login($this->user->email, 'password', '127.0.0.2', 'Agent 2');

    $this->authService->revokeAllForUser($this->user, RevocationReason::PasswordReset);

    expect(DeviceSession::where('user_id', $this->user->id)->active()->count())->toBe(0);
});

it('loginViaUser creates session and returns tokens', function (): void {
    $tokens = $this->authService->loginViaUser($this->user, '127.0.0.1', 'Agent');

    expect($tokens)->toHaveKeys(['access_token', 'refresh_token', 'expires_in']);
    expect(DeviceSession::where('user_id', $this->user->id)->count())->toBe(1);
});
