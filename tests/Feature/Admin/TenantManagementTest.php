<?php

use App\Models\Tenant;
use App\Models\User;

it('lists all tenants', function (): void {
    Tenant::factory()->count(3)->create();
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/tenants')
        ->assertSuccessful()
        ->assertJsonPath('total', 3);
});

it('filters tenants by status', function (): void {
    Tenant::factory()->create(['status' => 'active']);
    Tenant::factory()->create(['status' => 'disabled', 'disabled_at' => now()]);
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/tenants?status=active')
        ->assertSuccessful()
        ->assertJsonPath('total', 1);
});

it('filters tenants by search term', function (): void {
    Tenant::factory()->create(['name' => 'Alpha Corp']);
    Tenant::factory()->create(['name' => 'Beta Inc']);
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/tenants?search=Alpha')
        ->assertSuccessful()
        ->assertJsonPath('total', 1);
});

it('shows tenant details with usage stats', function (): void {
    $tenant = Tenant::factory()->create();
    actingAsSuperAdmin();

    $this->getJson("/admin/api/v1/tenants/{$tenant->id}")
        ->assertSuccessful()
        ->assertJsonStructure(['data', 'stats']);
});

it('shows tenant members', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['role' => 'member']);
    actingAsSuperAdmin();

    $this->getJson("/admin/api/v1/tenants/{$tenant->id}/members")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('disables a tenant', function (): void {
    $tenant = Tenant::factory()->create(['status' => 'active']);
    actingAsSuperAdmin();

    $this->postJson("/admin/api/v1/tenants/{$tenant->id}/disable")
        ->assertSuccessful();

    $tenant->refresh();
    expect($tenant->status->value)->toBe('disabled')
        ->and($tenant->disabled_at)->not->toBeNull();
});

it('enables a tenant', function (): void {
    $tenant = Tenant::factory()->create(['status' => 'disabled', 'disabled_at' => now()]);
    actingAsSuperAdmin();

    $this->postJson("/admin/api/v1/tenants/{$tenant->id}/enable")
        ->assertSuccessful();

    $tenant->refresh();
    expect($tenant->status->value)->toBe('active')
        ->and($tenant->disabled_at)->toBeNull();
});

it('force-logs out all tenant members', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['role' => 'member']);
    actingAsSuperAdmin();

    $this->postJson("/admin/api/v1/tenants/{$tenant->id}/force-logout")
        ->assertSuccessful()
        ->assertJsonPath('message', 'Force logged out 1 members.');
});

it('exports tenant data', function (): void {
    $tenant = Tenant::factory()->create();
    actingAsSuperAdmin();

    $this->getJson("/admin/api/v1/tenants/{$tenant->id}/export")
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['tenant', 'members']]);
});

it('soft deletes a tenant', function (): void {
    $tenant = Tenant::factory()->create();
    actingAsSuperAdmin();

    $this->deleteJson("/admin/api/v1/tenants/{$tenant->id}")
        ->assertNoContent();

    expect($tenant->fresh()->trashed())->toBeTrue();
});

it('can view a soft-deleted tenant', function (): void {
    $tenant = Tenant::factory()->create();
    $tenant->delete();
    actingAsSuperAdmin();

    $this->getJson("/admin/api/v1/tenants/{$tenant->id}")
        ->assertSuccessful();
});
