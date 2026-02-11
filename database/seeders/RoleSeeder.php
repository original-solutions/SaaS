<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Seed the Spatie Permission roles.
     *
     * - Global role: super-admin (tenant_id = null)
     * - Team-scoped template roles: owner, admin, member, readonly (tenant_id = null)
     *
     * These template roles apply across all teams. When a user joins a tenant,
     * the MembershipService assigns the role with team context.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Global super-admin role (no team scope)
        Role::findOrCreate('super-admin', 'api');

        // Team-scoped template roles
        Role::findOrCreate('owner', 'api');
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('member', 'api');
        Role::findOrCreate('readonly', 'api');
    }
}
