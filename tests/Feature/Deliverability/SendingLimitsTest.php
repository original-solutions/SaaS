<?php

use App\Models\SendingLimit;
use App\Models\Tenant;
use App\Services\DeliverabilityService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('allows sending when under the daily cap', function (): void {
    $tenant = Tenant::factory()->create();
    SendingLimit::create([
        'tenant_id' => $tenant->id,
        'channel' => 'email',
        'rate_per_minute' => 60,
        'daily_cap' => 100,
        'sent_today' => 10,
        'reset_date' => now()->startOfDay(),
    ]);

    $service = new DeliverabilityService;
    $result = $service->canSend('test@example.com', $tenant->id, 'email');

    expect($result['allowed'])->toBeTrue()
        ->and($result['reason'])->toBeNull();
});

it('blocks sending when daily cap is reached', function (): void {
    $tenant = Tenant::factory()->create();
    SendingLimit::create([
        'tenant_id' => $tenant->id,
        'channel' => 'email',
        'rate_per_minute' => 60,
        'daily_cap' => 100,
        'sent_today' => 100,
        'reset_date' => now()->startOfDay(),
    ]);

    $service = new DeliverabilityService;
    $result = $service->canSend('test@example.com', $tenant->id, 'email');

    expect($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('Daily sending cap reached.');
});

it('resets daily counter on a new day', function (): void {
    $tenant = Tenant::factory()->create();
    $limit = SendingLimit::create([
        'tenant_id' => $tenant->id,
        'channel' => 'email',
        'rate_per_minute' => 60,
        'daily_cap' => 100,
        'sent_today' => 100,
        'reset_date' => now()->subDay()->startOfDay(),
    ]);

    expect($limit->isAtDailyCap())->toBeFalse();
    $limit->refresh();
    expect($limit->sent_today)->toBe(0);
});

it('increments the daily counter', function (): void {
    $tenant = Tenant::factory()->create();
    $limit = SendingLimit::create([
        'tenant_id' => $tenant->id,
        'channel' => 'email',
        'rate_per_minute' => 60,
        'daily_cap' => 1000,
        'sent_today' => 5,
        'reset_date' => now()->startOfDay(),
    ]);

    $limit->incrementSentCount();
    $limit->refresh();

    expect($limit->sent_today)->toBe(6);
});

it('records sent message and increments limit', function (): void {
    $tenant = Tenant::factory()->create();
    SendingLimit::create([
        'tenant_id' => $tenant->id,
        'channel' => 'email',
        'rate_per_minute' => 60,
        'daily_cap' => 1000,
        'sent_today' => 0,
        'reset_date' => now()->startOfDay(),
    ]);

    $service = new DeliverabilityService;
    $log = $service->recordSent($tenant->id, 'email', 'user@example.com', 'Welcome!');

    expect($log->status)->toBe('sent')
        ->and($log->recipient)->toBe('user@example.com')
        ->and($log->subject)->toBe('Welcome!');

    $limit = SendingLimit::where('tenant_id', $tenant->id)->first();
    expect($limit->sent_today)->toBe(1);
});

it('allows sending when no limit is configured', function (): void {
    $tenant = Tenant::factory()->create();

    $service = new DeliverabilityService;
    $result = $service->canSend('test@example.com', $tenant->id, 'email');

    expect($result['allowed'])->toBeTrue();
});
