<?php

use App\Models\WebhookEvent;

it('accepts a valid webhook payload', function (): void {
    $payload = ['id' => 'evt_test_123', 'type' => 'payment_intent.succeeded', 'data' => ['object' => []]];

    $this->postJson('/api/v1/webhooks/stripe', $payload)
        ->assertSuccessful()
        ->assertJsonPath('message', 'Received.');

    $this->assertDatabaseHas('webhook_events', [
        'provider' => 'stripe',
        'event_id' => 'evt_test_123',
        'type' => 'payment_intent.succeeded',
    ]);
});

it('deduplicates events with same event_id', function (): void {
    $payload = ['id' => 'evt_duplicate_123', 'type' => 'payment_intent.succeeded', 'data' => ['object' => []]];

    $this->postJson('/api/v1/webhooks/stripe', $payload)->assertSuccessful();
    $this->postJson('/api/v1/webhooks/stripe', $payload)->assertSuccessful();

    expect(WebhookEvent::where('event_id', 'evt_duplicate_123')->count())->toBe(1);
});

it('rejects invalid signature when webhook secret is configured', function (): void {
    config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

    $this->postJson('/api/v1/webhooks/stripe', ['id' => 'evt_1', 'type' => 'test'])
        ->assertStatus(400)
        ->assertJsonPath('error', 'Missing signature.');
});

it('stores webhook payload as JSON', function (): void {
    $payload = ['id' => 'evt_json_1', 'type' => 'test', 'data' => ['key' => 'value']];

    $this->postJson('/api/v1/webhooks/stripe', $payload)->assertSuccessful();

    $event = WebhookEvent::where('event_id', 'evt_json_1')->first();
    expect($event->payload)->toBeArray()
        ->and($event->payload['data']['key'])->toBe('value');
});
