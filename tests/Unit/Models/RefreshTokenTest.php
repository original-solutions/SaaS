<?php

use App\Models\DeviceSession;
use App\Models\RefreshToken;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->session = DeviceSession::factory()->for($this->user)->create();
});

it('detects expired token', function (): void {
    $token = RefreshToken::factory()->for($this->user)->expired()->create([
        'device_session_id' => $this->session->id,
    ]);

    expect($token->isExpired())->toBeTrue();
});

it('detects non-expired token', function (): void {
    $token = RefreshToken::factory()->for($this->user)->create([
        'device_session_id' => $this->session->id,
    ]);

    expect($token->isExpired())->toBeFalse();
});

it('detects revoked token', function (): void {
    $token = RefreshToken::factory()->for($this->user)->revoked()->create([
        'device_session_id' => $this->session->id,
    ]);

    expect($token->isRevoked())->toBeTrue();
});

it('detects used token', function (): void {
    $token = RefreshToken::factory()->for($this->user)->used()->create([
        'device_session_id' => $this->session->id,
    ]);

    expect($token->isUsed())->toBeTrue();
});

it('is valid when not expired, revoked, or used', function (): void {
    $token = RefreshToken::factory()->for($this->user)->create([
        'device_session_id' => $this->session->id,
    ]);

    expect($token->isValid())->toBeTrue();
});

it('is not valid when expired', function (): void {
    $token = RefreshToken::factory()->for($this->user)->expired()->create([
        'device_session_id' => $this->session->id,
    ]);

    expect($token->isValid())->toBeFalse();
});

it('belongs to user and device session', function (): void {
    $token = RefreshToken::factory()->for($this->user)->create([
        'device_session_id' => $this->session->id,
    ]);

    expect($token->user->id)->toBe($this->user->id);
    expect($token->deviceSession->id)->toBe($this->session->id);
});
