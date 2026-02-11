<?php

use App\Models\DeviceSession;
use App\Models\LoginEvent;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'password' => 'password',
    ]);
});

it('returns token pair on successful login', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'access_token',
            'refresh_token',
            'expires_in',
            'user' => ['id', 'name', 'email'],
            'tenants',
        ]);
});

it('returns 401 for invalid credentials', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $this->user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized();
});

it('returns 401 for non-existent user', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password',
    ]);

    $response->assertUnauthorized();
});

it('returns 403 for locked user', function (): void {
    $locked = User::factory()->locked()->create([
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $locked->email,
        'password' => 'password',
    ]);

    $response->assertForbidden()
        ->assertJsonPath('error', 'ACCOUNT_LOCKED');
});

it('creates a device session on login', function (): void {
    $this->postJson('/api/v1/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    expect(DeviceSession::where('user_id', $this->user->id)->count())->toBe(1);
});

it('records a login event on success', function (): void {
    $this->postJson('/api/v1/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    expect(LoginEvent::where('user_id', $this->user->id)
        ->where('event_type', 'login_success')
        ->count())->toBe(1);
});

it('records a login event on failure', function (): void {
    $this->postJson('/api/v1/auth/login', [
        'email' => $this->user->email,
        'password' => 'wrong',
    ]);

    expect(LoginEvent::where('email', $this->user->email)
        ->where('event_type', 'login_failed')
        ->count())->toBe(1);
});

it('updates last_login_at on successful login', function (): void {
    expect($this->user->last_login_at)->toBeNull();

    $this->postJson('/api/v1/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $this->user->refresh();
    expect($this->user->last_login_at)->not->toBeNull();
});

it('validates required fields', function (): void {
    $response = $this->postJson('/api/v1/auth/login', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('returns expires_in in seconds', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $expiresIn = $response->json('expires_in');
    $ttlMinutes = (int) config('saas.access_token_ttl', 15);

    expect($expiresIn)->toBe($ttlMinutes * 60);
});
