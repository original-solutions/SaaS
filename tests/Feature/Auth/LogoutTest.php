<?php

use App\Models\LoginEvent;
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

it('logs out successfully', function (): void {
    $response = $this->withToken($this->tokens['access_token'])
        ->postJson('/api/v1/auth/logout', [
            'refresh_token' => $this->tokens['refresh_token'],
        ]);

    $response->assertSuccessful()
        ->assertJson(['message' => 'Logged out.']);
});

it('records logout event', function (): void {
    $this->withToken($this->tokens['access_token'])
        ->postJson('/api/v1/auth/logout', [
            'refresh_token' => $this->tokens['refresh_token'],
        ]);

    expect(LoginEvent::where('user_id', $this->user->id)
        ->where('event_type', 'logout')
        ->count())->toBe(1);
});

it('revokes device session on logout', function (): void {
    $this->withToken($this->tokens['access_token'])
        ->postJson('/api/v1/auth/logout', [
            'refresh_token' => $this->tokens['refresh_token'],
        ]);

    $session = $this->user->deviceSessions()->first();
    expect($session->isRevoked())->toBeTrue();
});

it('returns 401 for unauthenticated logout', function (): void {
    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertUnauthorized();
});
