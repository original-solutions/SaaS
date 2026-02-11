<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MembershipService;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->membershipService = app(MembershipService::class);
});

it('allows creating a permission and assigning it to a role', function () {
    $permission = Permission::create(['name' => 'invoices.export', 'guard_name' => 'api']);
    $role = Role::findByName('admin', 'api');

    $role->givePermissionTo($permission);

    expect($role->hasPermissionTo('invoices.export'))->toBeTrue();
});

it('verifies user inherits permission via role', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Admin);

    $permission = Permission::create(['name' => 'reports.view', 'guard_name' => 'api']);
    $role = Role::findByName('admin', 'api');
    $role->givePermissionTo($permission);

    setPermissionsTeamId($tenant->id);
    $user->unsetRelation('roles');
    $user->unsetRelation('permissions');

    expect($user->hasPermissionTo('reports.view'))->toBeTrue();
});

it('verifies user without the role does not inherit the permission', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Member);

    $permission = Permission::create(['name' => 'invoices.delete', 'guard_name' => 'api']);
    $role = Role::findByName('admin', 'api');
    $role->givePermissionTo($permission);

    setPermissionsTeamId($tenant->id);
    $user->unsetRelation('roles');
    $user->unsetRelation('permissions');

    expect($user->hasPermissionTo('invoices.delete'))->toBeFalse();
});

it('allows direct permission assignment to a user', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Member);

    $permission = Permission::create(['name' => 'special.action', 'guard_name' => 'api']);

    setPermissionsTeamId($tenant->id);
    $user->givePermissionTo($permission);
    $user->unsetRelation('permissions');

    expect($user->hasPermissionTo('special.action'))->toBeTrue();
});

it('scopes permissions to the correct tenant', function () {
    $user = User::factory()->create();
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $this->membershipService->addMember($tenantA, $user, TenantRole::Admin);
    $this->membershipService->addMember($tenantB, $user, TenantRole::Member);

    $permission = Permission::create(['name' => 'dashboard.admin', 'guard_name' => 'api']);
    $adminRole = Role::findByName('admin', 'api');
    $adminRole->givePermissionTo($permission);

    // User has admin role in tenant A → should have permission
    setPermissionsTeamId($tenantA->id);
    $user->unsetRelation('roles');
    $user->unsetRelation('permissions');
    expect($user->hasPermissionTo('dashboard.admin'))->toBeTrue();

    // User has member role in tenant B → should NOT have permission
    setPermissionsTeamId($tenantB->id);
    $user->unsetRelation('roles');
    $user->unsetRelation('permissions');
    expect($user->hasPermissionTo('dashboard.admin'))->toBeFalse();
});
