<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    /**
     * Set the Spatie team context so role checks are scoped to the given tenant.
     */
    private function setTeamContext(User $user, Tenant $tenant): void
    {
        setPermissionsTeamId($tenant->id);
        $user->unsetRelation('roles');
    }

    /**
     * Determine whether the user can view any models.
     * Any authenticated user can list their own tenants.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        $this->setTeamContext($user, $tenant);

        return $user->hasAnyRole(['owner', 'admin', 'member', 'readonly']);
    }

    /**
     * Determine whether the user can create models.
     * Any authenticated user can create a new tenant.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        $this->setTeamContext($user, $tenant);

        return $user->hasAnyRole(['owner', 'admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        $this->setTeamContext($user, $tenant);

        return $user->hasRole('owner');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tenant $tenant): bool
    {
        $this->setTeamContext($user, $tenant);

        return $user->hasRole('owner');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tenant $tenant): bool
    {
        $this->setTeamContext($user, $tenant);

        return $user->hasRole('owner');
    }
}
