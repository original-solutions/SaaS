<?php

use App\Enums\RevocationReason;
use App\Models\DeviceSession;
use App\Models\RefreshToken;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->session = DeviceSession::factory()->for($this->user)->create();
});

it('has active scope', function (): void {
    DeviceSession::factory()->for($this->user)->revoked()->create();

    expect(DeviceSession::active()->count())->toBe(1);
    expect(DeviceSession::revoked()->count())->toBe(1);
});

it('revokes session and sets reason', function (): void {
    $this->session->revoke(RevocationReason::Logout);

    $this->session->refresh();
    expect($this->session->isRevoked())->toBeTrue();
    expect($this->session->revocation_reason)->toBe(RevocationReason::Logout);
});

it('stores revocation_reason correctly', function (): void {
    $this->session->revoke(RevocationReason::RefreshReuseDetected);

    $this->session->refresh();
    expect($this->session->revocation_reason)->toBe(RevocationReason::RefreshReuseDetected);
    expect($this->session->revocation_reason->value)->toBe('refresh_reuse_detected');
});

it('also revokes child refresh tokens', function (): void {
    RefreshToken::factory()->for($this->user)->create([
        'device_session_id' => $this->session->id,
    ]);

    $this->session->revoke(RevocationReason::Logout);

    $token = RefreshToken::where('device_session_id', $this->session->id)->first();
    expect($token->isRevoked())->toBeTrue();
});

it('belongs to user', function (): void {
    expect($this->session->user->id)->toBe($this->user->id);
});
