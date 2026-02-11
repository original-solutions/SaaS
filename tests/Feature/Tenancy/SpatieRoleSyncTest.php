<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MembershipService;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->membershipService = app(MembershipService::class);
});

it('assigns Spatie role scoped to tenant when adding member', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    $this->membershipService->addMember($tenant, $user, TenantRole::Owner);

    // Set team context to verify
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->unsetRelation('roles');

    expect($user->hasRole('owner'))->toBeTrue();
});

it('user has owner in tenant A but member in tenant B', function (): void {
    $user = User::factory()->create();
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $this->membershipService->addMember($tenantA, $user, TenantRole::Owner);
    $this->membershipService->addMember($tenantB, $user, TenantRole::Member);

    // Check tenant A
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenantA->id);
    $user->unsetRelation('roles');
    expect($user->hasRole('owner'))->toBeTrue();
    expect($user->hasRole('member'))->toBeFalse();

    // Check tenant B
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenantB->id);
    $user->unsetRelation('roles');
    expect($user->hasRole('member'))->toBeTrue();
    expect($user->hasRole('owner'))->toBeFalse();
});

it('changing role updates both pivot and Spatie role', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    $this->membershipService->addMember($tenant, $user, TenantRole::Member);
    $this->membershipService->changeRole($tenant, $user, TenantRole::Admin);

    // Check pivot
    $pivot = $tenant->users()->where('users.id', $user->id)->first();
    expect($pivot->pivot->role)->toBe('admin');

    // Check Spatie role
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->unsetRelation('roles');
    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->hasRole('member'))->toBeFalse();
});

it('removing membership revokes tenant-scoped role', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    $this->membershipService->addMember($tenant, $user, TenantRole::Member);
    $this->membershipService->removeMember($tenant, $user);

    // User should no longer have the role
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->unsetRelation('roles');
    expect($user->hasRole('member'))->toBeFalse();

    // Pivot should be gone
    expect($tenant->users()->where('users.id', $user->id)->exists())->toBeFalse();
});

it('tenant_user role stays in sync with Spatie role', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    $this->membershipService->addMember($tenant, $user, TenantRole::Owner);

    // Verify both are 'owner'
    $pivot = $tenant->users()->where('users.id', $user->id)->first();
    expect($pivot->pivot->role)->toBe('owner');

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->unsetRelation('roles');
    expect($user->hasRole('owner'))->toBeTrue();
});

it('detects sole owner', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $tenant = Tenant::factory()->create();

    $this->membershipService->addMember($tenant, $user1, TenantRole::Owner);
    expect($this->membershipService->isSoleOwner($tenant, $user1))->toBeTrue();

    // Add second owner
    $this->membershipService->addMember($tenant, $user2, TenantRole::Owner);
    expect($this->membershipService->isSoleOwner($tenant, $user1))->toBeFalse();
});
