<?php

use App\Enums\WebhookEventStatus;
use App\Models\WebhookEvent;

it('creates a webhook event', function (): void {
    $event = WebhookEvent::factory()->create();

    expect($event->status)->toBe(WebhookEventStatus::Received)
        ->and($event->payload)->toBeArray()
        ->and($event->attempts)->toBe(0);
});

it('marks a webhook event as processed', function (): void {
    $event = WebhookEvent::factory()->processed()->create();

    expect($event->status)->toBe(WebhookEventStatus::Processed)
        ->and($event->processed_at)->not->toBeNull();
});

it('marks a webhook event as failed', function (): void {
    $event = WebhookEvent::factory()->failed()->create();

    expect($event->status)->toBe(WebhookEventStatus::Failed)
        ->and($event->last_error)->not->toBeNull();
});

it('enforces provider + event_id uniqueness', function (): void {
    WebhookEvent::factory()->create(['provider' => 'stripe', 'event_id' => 'evt_123']);

    expect(fn () => WebhookEvent::factory()->create(['provider' => 'stripe', 'event_id' => 'evt_123']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
