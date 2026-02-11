<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MembershipService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->membershipService = app(MembershipService::class);
});

it('allows owner to view tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Owner);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/tenants/{$tenant->id}")
        ->assertSuccessful();
});

it('allows admin to view tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Admin);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/tenants/{$tenant->id}")
        ->assertSuccessful();
});

it('allows member to view tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Member);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/tenants/{$tenant->id}")
        ->assertSuccessful();
});

it('allows readonly to view tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Readonly);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/tenants/{$tenant->id}")
        ->assertSuccessful();
});

it('denies non-member from viewing tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/tenants/{$tenant->id}")
        ->assertForbidden();
});

it('allows owner to update tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Owner);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/tenants/{$tenant->id}", ['name' => 'Updated Name'])
        ->assertSuccessful();
});

it('allows admin to update tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Admin);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/tenants/{$tenant->id}", ['name' => 'Updated Name'])
        ->assertSuccessful();
});

it('denies member from updating tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Member);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/tenants/{$tenant->id}", ['name' => 'Updated Name'])
        ->assertForbidden();
});

it('denies readonly from updating tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Readonly);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/tenants/{$tenant->id}", ['name' => 'Updated Name'])
        ->assertForbidden();
});

it('allows owner to delete tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Owner);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/tenants/{$tenant->id}")
        ->assertSuccessful();
});

it('denies admin from deleting tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Admin);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/tenants/{$tenant->id}")
        ->assertForbidden();
});

it('denies member from deleting tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Member);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/tenants/{$tenant->id}")
        ->assertForbidden();
});

it('denies readonly from deleting tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Readonly);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/tenants/{$tenant->id}")
        ->assertForbidden();
});

it('allows any authenticated user to create a tenant', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/tenants', [
            'name' => 'New Tenant',
            'slug' => 'new-tenant',
        ])
        ->assertCreated();
});

it('allows any authenticated user to list their tenants', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/tenants')
        ->assertSuccessful();
});
