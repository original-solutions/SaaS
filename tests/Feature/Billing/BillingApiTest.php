<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;

it('returns current billing status', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($user, ['role' => 'owner']);
    $plan = Plan::factory()->create(['name' => 'Pro']);
    Subscription::factory()->active()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->withHeader('X-Tenant-ID', (string) $tenant->id)
        ->getJson('/api/v1/billing')
        ->assertSuccessful()
        ->assertJsonPath('data.plan.name', 'Pro')
        ->assertJsonPath('data.is_read_only', false);
});

it('returns null when no subscription exists', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($user, ['role' => 'owner']);

    $this->actingAs($user, 'sanctum')
        ->withHeader('X-Tenant-ID', (string) $tenant->id)
        ->getJson('/api/v1/billing')
        ->assertSuccessful()
        ->assertJsonPath('data', null);
});

it('returns available plans', function (): void {
    Plan::factory()->count(2)->create(['is_active' => true]);
    Plan::factory()->inactive()->create();

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/billing/plans')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});
