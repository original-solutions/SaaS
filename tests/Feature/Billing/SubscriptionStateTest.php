<?php

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function (): void {
    $this->admin = User::factory()->create();
    $this->tenant = Tenant::factory()->create();
    $this->tenant->users()->attach($this->admin, ['role' => 'owner']);
    $this->plan = Plan::factory()->create();
    $this->actingAs($this->admin, 'sanctum');
});

it('allows full access with trialing subscription', function (): void {
    Subscription::factory()->trialing()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->postJson('/api/v1/tags', ['name' => 'Test Tag'])
        ->assertStatus(201);
});

it('allows full access with active subscription', function (): void {
    Subscription::factory()->active()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->postJson('/api/v1/tags', ['name' => 'Test Tag'])
        ->assertStatus(201);
});

it('allows full access when past due but in grace period', function (): void {
    Subscription::factory()->pastDueInGrace()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->postJson('/api/v1/tags', ['name' => 'Test Tag'])
        ->assertStatus(201);
});

it('blocks writes when past due and grace expired', function (): void {
    Subscription::factory()->pastDueGraceExpired()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->postJson('/api/v1/tags', ['name' => 'Test Tag'])
        ->assertForbidden()
        ->assertJsonPath('error', 'SUBSCRIPTION_READ_ONLY');
});

it('allows GET requests when read-only', function (): void {
    Subscription::factory()->canceled()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->getJson('/api/v1/tags')
        ->assertSuccessful();
});

it('blocks writes when canceled', function (): void {
    Subscription::factory()->canceled()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->postJson('/api/v1/tags', ['name' => 'Test Tag'])
        ->assertForbidden()
        ->assertJsonPath('error', 'SUBSCRIPTION_READ_ONLY');
});

it('blocks writes when unpaid', function (): void {
    Subscription::factory()->unpaid()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->postJson('/api/v1/tags', ['name' => 'Test Tag'])
        ->assertForbidden()
        ->assertJsonPath('error', 'SUBSCRIPTION_READ_ONLY');
});

it('allows billing endpoints in read-only mode', function (): void {
    Subscription::factory()->canceled()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->getJson('/api/v1/billing')
        ->assertSuccessful();
});

it('logs subscription status changes', function (): void {
    $subscription = Subscription::factory()->active()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
    ]);

    $subscription->update(['status' => SubscriptionStatus::Canceled, 'canceled_at' => now()]);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Subscription::class,
        'subject_id' => $subscription->id,
        'event' => 'updated',
    ]);
});
