<?php

use App\Models\EmailSuppression;
use App\Models\MessageLog;
use App\Models\Tenant;
use App\Services\DeliverabilityService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('suppresses globally suppressed emails', function (): void {
    $tenant = Tenant::factory()->create();
    EmailSuppression::factory()->create([
        'tenant_id' => null,
        'email' => 'blocked@example.com',
        'reason' => 'hard_bounce',
    ]);

    $service = new DeliverabilityService;
    $result = $service->canSend('blocked@example.com', $tenant->id);

    expect($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('Email is on the suppression list.');
});

it('suppresses tenant-specific suppressed emails', function (): void {
    $tenant = Tenant::factory()->create();
    EmailSuppression::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'tenant-blocked@example.com',
        'reason' => 'complaint',
    ]);

    $service = new DeliverabilityService;
    $result = $service->canSend('tenant-blocked@example.com', $tenant->id);

    expect($result['allowed'])->toBeFalse();
});

it('allows non-suppressed emails', function (): void {
    $tenant = Tenant::factory()->create();

    $service = new DeliverabilityService;
    $result = $service->canSend('clean@example.com', $tenant->id);

    expect($result['allowed'])->toBeTrue();
});

it('tenant suppression does not affect other tenants', function (): void {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    EmailSuppression::factory()->create([
        'tenant_id' => $tenant1->id,
        'email' => 'specific@example.com',
        'reason' => 'complaint',
    ]);

    $service = new DeliverabilityService;

    expect($service->canSend('specific@example.com', $tenant1->id)['allowed'])->toBeFalse();
    expect($service->canSend('specific@example.com', $tenant2->id)['allowed'])->toBeTrue();
});

it('records hard bounce and auto-suppresses', function (): void {
    $tenant = Tenant::factory()->create();
    MessageLog::factory()->create([
        'tenant_id' => $tenant->id,
        'recipient' => 'bounced@example.com',
        'status' => 'sent',
    ]);

    $service = new DeliverabilityService;
    $service->recordBounce('bounced@example.com', $tenant->id, 'hard', 'Mailbox not found');

    expect(EmailSuppression::where('email', 'bounced@example.com')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(MessageLog::where('recipient', 'bounced@example.com')->first()->status)->toBe('bounced');
});

it('does not auto-suppress on soft bounce', function (): void {
    $tenant = Tenant::factory()->create();
    MessageLog::factory()->create([
        'tenant_id' => $tenant->id,
        'recipient' => 'softbounce@example.com',
        'status' => 'sent',
    ]);

    $service = new DeliverabilityService;
    $service->recordBounce('softbounce@example.com', $tenant->id, 'soft', 'Mailbox full');

    expect(EmailSuppression::where('email', 'softbounce@example.com')->exists())->toBeFalse();
});

it('records complaint and suppresses', function (): void {
    $tenant = Tenant::factory()->create();
    MessageLog::factory()->create([
        'tenant_id' => $tenant->id,
        'recipient' => 'complainer@example.com',
        'status' => 'sent',
    ]);

    $service = new DeliverabilityService;
    $service->recordComplaint('complainer@example.com', $tenant->id, 'Spam report');

    expect(EmailSuppression::where('email', 'complainer@example.com')->exists())->toBeTrue();
    expect(MessageLog::where('recipient', 'complainer@example.com')->first()->status)->toBe('complained');
});

it('unsuppresses an email', function (): void {
    $tenant = Tenant::factory()->create();
    EmailSuppression::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'unsuppress@example.com',
    ]);

    $service = new DeliverabilityService;
    $result = $service->unsuppress('unsuppress@example.com', $tenant->id);

    expect($result)->toBeTrue();
    expect(EmailSuppression::where('email', 'unsuppress@example.com')->exists())->toBeFalse();
});
