<?php

namespace App\Services;

use App\Enums\RevocationReason;
use App\Models\Tenant;
use App\Models\User;

class AdminTenantService
{
    public function __construct(protected AuthService $authService) {}

    /**
     * Disable a tenant.
     */
    public function disable(Tenant $tenant): void
    {
        $tenant->update([
            'status' => 'disabled',
            'disabled_at' => now(),
        ]);
    }

    /**
     * Enable a tenant.
     */
    public function enable(Tenant $tenant): void
    {
        $tenant->update([
            'status' => 'active',
            'disabled_at' => null,
        ]);
    }

    /**
     * Force logout all tenant members by revoking all sessions.
     */
    public function forceLogout(Tenant $tenant): int
    {
        $count = 0;
        $tenant->users()->each(function (User $user) use (&$count): void {
            $this->authService->revokeAllForUser($user, RevocationReason::AdminRevoked);
            $count++;
        });

        return $count;
    }

    /**
     * Export tenant data.
     *
     * @return array<string, mixed>
     */
    public function exportData(Tenant $tenant): array
    {
        return [
            'tenant' => $tenant->toArray(),
            'members' => $tenant->users()->get()->toArray(),
        ];
    }

    /**
     * Delete a tenant (soft delete).
     */
    public function delete(Tenant $tenant): void
    {
        $tenant->delete();
    }
}
