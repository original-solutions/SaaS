<?php

use App\Models\DeviceSession;
use App\Models\MagicLoginToken;
use App\Models\User;

it('sends magic link for valid email', function (): void {
    config(['saas.magic_link_login' => true]);

    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/magic-link', [
        'email' => $user->email,
    ]);

    $response->assertSuccessful()
        ->assertJson(['message' => 'If the email exists, a magic link has been sent.']);

    expect(MagicLoginToken::where('user_id', $user->id)->count())->toBe(1);
});

it('returns success even for non-existent email to prevent enumeration', function (): void {
    config(['saas.magic_link_login' => true]);

    $response = $this->postJson('/api/v1/auth/magic-link', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertSuccessful();
});

it('verifies valid magic link token', function (): void {
    config(['saas.magic_link_login' => true]);

    $user = User::factory()->create();

    $plainToken = \Illuminate\Support\Str::random(64);
    MagicLoginToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->postJson('/api/v1/auth/magic-link/verify', [
        'token' => $plainToken,
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['access_token', 'refresh_token', 'expires_in', 'user', 'tenants']);
});

it('creates device session on magic link login', function (): void {
    config(['saas.magic_link_login' => true]);

    $user = User::factory()->create();

    $plainToken = \Illuminate\Support\Str::random(64);
    MagicLoginToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addMinutes(15),
    ]);

    $this->postJson('/api/v1/auth/magic-link/verify', [
        'token' => $plainToken,
    ]);

    expect(DeviceSession::where('user_id', $user->id)->count())->toBe(1);
});

it('rejects expired magic link token', function (): void {
    config(['saas.magic_link_login' => true]);

    $user = User::factory()->create();

    $plainToken = \Illuminate\Support\Str::random(64);
    MagicLoginToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->postJson('/api/v1/auth/magic-link/verify', [
        'token' => $plainToken,
    ]);

    $response->assertUnauthorized();
});

it('rejects used magic link token', function (): void {
    config(['saas.magic_link_login' => true]);

    $user = User::factory()->create();

    $plainToken = \Illuminate\Support\Str::random(64);
    MagicLoginToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addMinutes(15),
        'used_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/magic-link/verify', [
        'token' => $plainToken,
    ]);

    $response->assertUnauthorized();
});

it('returns 403 when magic link feature is disabled', function (): void {
    config(['saas.magic_link_login' => false]);

    $response = $this->postJson('/api/v1/auth/magic-link', [
        'email' => 'test@example.com',
    ]);

    $response->assertForbidden();
});

it('rejects magic link login for locked user', function (): void {
    config(['saas.magic_link_login' => true]);

    $user = User::factory()->locked()->create();

    $plainToken = \Illuminate\Support\Str::random(64);
    MagicLoginToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->postJson('/api/v1/auth/magic-link/verify', [
        'token' => $plainToken,
    ]);

    $response->assertForbidden();
});
