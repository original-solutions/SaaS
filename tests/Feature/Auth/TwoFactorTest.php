<?php

use App\Models\User;
use App\Services\TwoFactorService;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('enables 2FA and returns secret and QR URI', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/auth/two-factor/enable');

    $response->assertSuccessful()
        ->assertJsonStructure(['secret', 'qr_uri', 'recovery_codes']);

    $this->user->refresh();
    expect($this->user->two_factor_secret)->not->toBeNull();
});

it('returns recovery codes on enable', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/auth/two-factor/enable');

    $codes = $response->json('recovery_codes');
    expect($codes)->toBeArray()->toHaveCount(8);
});

it('prevents enabling 2FA if already enabled', function (): void {
    $user = User::factory()->withTwoFactor()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/two-factor/enable');

    $response->assertStatus(422);
});

it('confirms 2FA with valid TOTP code', function (): void {
    $twoFactorService = app(TwoFactorService::class);
    $result = $twoFactorService->enable($this->user);

    // Generate a valid code from the secret
    $google2fa = new \PragmaRX\Google2FA\Google2FA;
    $validCode = $google2fa->getCurrentOtp($result['secret']);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/auth/two-factor/confirm', [
            'code' => $validCode,
        ]);

    $response->assertSuccessful()
        ->assertJson(['message' => 'Two-factor authentication confirmed.']);
});

it('rejects invalid TOTP code', function (): void {
    $twoFactorService = app(TwoFactorService::class);
    $twoFactorService->enable($this->user);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/auth/two-factor/confirm', [
            'code' => '000000',
        ]);

    $response->assertStatus(422);
});

it('disables 2FA with password confirmation', function (): void {
    $user = User::factory()->withTwoFactor()->create(['password' => 'password']);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/auth/two-factor', [
            'password' => 'password',
        ]);

    $response->assertSuccessful();

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull();
    expect($user->two_factor_recovery_codes)->toBeNull();
});

it('rejects disable 2FA with wrong password', function (): void {
    $user = User::factory()->withTwoFactor()->create(['password' => 'password']);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/auth/two-factor', [
            'password' => 'wrong-password',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('regenerates recovery codes', function (): void {
    $user = User::factory()->withTwoFactor()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/two-factor/recovery-codes');

    $response->assertSuccessful()
        ->assertJsonStructure(['recovery_codes']);

    expect($response->json('recovery_codes'))->toHaveCount(8);
});

it('returns 422 when regenerating codes without 2FA enabled', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/auth/two-factor/recovery-codes');

    $response->assertStatus(422);
});

it('returns 403 when 2FA feature is disabled', function (): void {
    config(['saas.two_factor_enabled' => false]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/auth/two-factor/enable');

    $response->assertForbidden();
});

it('recovery code works as one-time use', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    $twoFactorService = app(TwoFactorService::class);

    // The factory sets recovery codes: code-1 through code-8
    $used = $twoFactorService->verifyRecoveryCode($user, 'code-1');
    expect($used)->toBeTrue();

    // Second use should fail
    $user->refresh();
    $usedAgain = $twoFactorService->verifyRecoveryCode($user, 'code-1');
    expect($usedAgain)->toBeFalse();
});

it('requires authentication for 2FA operations', function (): void {
    $this->postJson('/api/v1/auth/two-factor/enable')->assertUnauthorized();
    $this->postJson('/api/v1/auth/two-factor/confirm', ['code' => '123456'])->assertUnauthorized();
    $this->deleteJson('/api/v1/auth/two-factor', ['password' => 'pass'])->assertUnauthorized();
    $this->postJson('/api/v1/auth/two-factor/recovery-codes')->assertUnauthorized();
});
