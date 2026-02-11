<?php

namespace App\Services;

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class MembershipService
{
    /**
     * Attach a user to a tenant with a role.
     * Syncs both the pivot table and Spatie role.
     */
    public function addMember(Tenant $tenant, User $user, TenantRole $role): void
    {
        // Create tenant_user pivot entry
        $tenant->users()->syncWithoutDetaching([
            $user->id => [
                'role' => $role->value,
                'joined_at' => now(),
            ],
        ]);

        // Set Spatie team context and assign role
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->unsetRelation('roles');

        // Find or create the role for the api guard
        $spatieRole = \Spatie\Permission\Models\Role::findOrCreate($role->value, 'api');
        $user->assignRole($spatieRole);
    }

    /**
     * Change a user's role within a tenant.
     */
    public function changeRole(Tenant $tenant, User $user, TenantRole $newRole): void
    {
        // Update pivot
        $tenant->users()->updateExistingPivot($user->id, [
            'role' => $newRole->value,
        ]);

        // Sync Spatie role
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->unsetRelation('roles');
        $spatieRole = \Spatie\Permission\Models\Role::findOrCreate($newRole->value, 'api');
        $user->syncRoles([$spatieRole]);
    }

    /**
     * Remove a user from a tenant.
     */
    public function removeMember(Tenant $tenant, User $user): void
    {
        // Remove Spatie roles for this tenant
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->unsetRelation('roles');
        $user->roles()->detach();

        // Remove pivot
        $tenant->users()->detach($user->id);
    }

    /**
     * Check if a user is the sole owner of a tenant.
     */
    public function isSoleOwner(Tenant $tenant, User $user): bool
    {
        $ownerCount = $tenant->users()
            ->wherePivot('role', TenantRole::Owner->value)
            ->count();

        $isOwner = $tenant->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', TenantRole::Owner->value)
            ->exists();

        return $isOwner && $ownerCount === 1;
    }
}
