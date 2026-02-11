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

it('super admin bypasses all gates via Gate::before', function () {
    $superAdmin = User::factory()->create(['is_super_admin' => true]);
    $tenant = Tenant::factory()->create();

    // Super admin is NOT a member of this tenant, but can do everything
    $this->actingAs($superAdmin, 'sanctum')
        ->getJson("/api/v1/tenants/{$tenant->id}")
        ->assertSuccessful();

    $this->actingAs($superAdmin, 'sanctum')
        ->putJson("/api/v1/tenants/{$tenant->id}", ['name' => 'SA Updated'])
        ->assertSuccessful();

    $this->actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/v1/tenants/{$tenant->id}")
        ->assertSuccessful();
});

it('non-super-admin does not get gate bypass', function () {
    $user = User::factory()->create(['is_super_admin' => false]);
    $tenant = Tenant::factory()->create();

    // Regular user with no membership can't access the tenant
    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/tenants/{$tenant->id}")
        ->assertForbidden();
});

it('super admin can view tenants they are not a member of', function () {
    $superAdmin = User::factory()->create(['is_super_admin' => true]);
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $owner, TenantRole::Owner);

    $this->actingAs($superAdmin, 'sanctum')
        ->getJson("/api/v1/tenants/{$tenant->id}")
        ->assertSuccessful();
});

it('super admin can delete tenants they are not an owner of', function () {
    $superAdmin = User::factory()->create(['is_super_admin' => true]);
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $owner, TenantRole::Owner);

    $this->actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/v1/tenants/{$tenant->id}")
        ->assertSuccessful();
});

it('is-super-admin gate returns true for super admins', function () {
    $superAdmin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($superAdmin, 'sanctum');

    expect($superAdmin->can('is-super-admin'))->toBeTrue();
});

it('is-super-admin gate returns false for regular users', function () {
    $user = User::factory()->create(['is_super_admin' => false]);

    $this->actingAs($user, 'sanctum');

    expect($user->can('is-super-admin'))->toBeFalse();
});
