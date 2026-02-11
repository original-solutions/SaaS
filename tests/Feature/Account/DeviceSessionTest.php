<?php

use App\Enums\RevocationReason;
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

it('lists active device sessions', function (): void {
    // Create a second session
    $this->authService->login($this->user->email, 'password', '127.0.0.2', 'Agent 2');

    $response = $this->withToken($this->tokens['access_token'])
        ->getJson('/api/v1/account/sessions');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

it('does not list revoked sessions', function (): void {
    // Create and revoke a session
    $tokens2 = $this->authService->login($this->user->email, 'password', '127.0.0.2', 'Agent 2');
    $session2 = $this->user->deviceSessions()->latest('id')->first();
    $session2->revoke(RevocationReason::Logout);

    $response = $this->withToken($this->tokens['access_token'])
        ->getJson('/api/v1/account/sessions');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(1);
});

it('revokes a specific device session', function (): void {
    $session = $this->user->deviceSessions()->first();

    $response = $this->withToken($this->tokens['access_token'])
        ->deleteJson("/api/v1/account/sessions/{$session->id}");

    $response->assertSuccessful()
        ->assertJson(['message' => 'Session revoked.']);

    $session->refresh();
    expect($session->isRevoked())->toBeTrue();
});

it('cannot revoke another user session', function (): void {
    $otherUser = User::factory()->create(['password' => 'password']);
    $otherTokens = $this->authService->login($otherUser->email, 'password', '127.0.0.1', 'Agent');
    $otherSession = $otherUser->deviceSessions()->first();

    $response = $this->withToken($this->tokens['access_token'])
        ->deleteJson("/api/v1/account/sessions/{$otherSession->id}");

    $response->assertForbidden();
});

it('revokes all sessions', function (): void {
    // Create second session
    $this->authService->login($this->user->email, 'password', '127.0.0.2', 'Agent 2');
    expect($this->user->deviceSessions()->active()->count())->toBe(2);

    $response = $this->withToken($this->tokens['access_token'])
        ->deleteJson('/api/v1/account/sessions');

    $response->assertSuccessful();
    expect($this->user->deviceSessions()->active()->count())->toBe(0);
});

it('revoked session invalidates its refresh tokens', function (): void {
    $session = $this->user->deviceSessions()->first();
    $session->revoke(RevocationReason::Logout);

    // Try to refresh
    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $this->tokens['refresh_token'],
    ]);

    $response->assertUnauthorized();
});

it('requires authentication', function (): void {
    $this->getJson('/api/v1/account/sessions')->assertUnauthorized();
    $this->deleteJson('/api/v1/account/sessions/1')->assertUnauthorized();
    $this->deleteJson('/api/v1/account/sessions')->assertUnauthorized();
});
