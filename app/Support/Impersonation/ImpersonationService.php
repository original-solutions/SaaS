<?php

namespace App\Support\Impersonation;

use App\Models\User;

class ImpersonationService
{
    /**
     * Start impersonation of a target user.
     */
    public function start(User $admin, User $targetUser, ?string $reason = null): array
    {
        // Cannot impersonate other super admins
        if ($targetUser->isSuperAdmin()) {
            return ['success' => false, 'error' => 'CANNOT_IMPERSONATE_SUPER_ADMIN'];
        }

        $expiresAt = now()->addMinutes(config('saas.impersonation_ttl', 60));

        // Log the impersonation start
        activity()
            ->event('impersonation_started')
            ->causedBy($admin)
            ->performedOn($targetUser)
            ->withProperties([
                'reason' => $reason,
                'expires_at' => $expiresAt->toIso8601String(),
            ])
            ->useLog('impersonation')
            ->log('Started impersonating user');

        // Create a new Sanctum token for the target user
        $token = $targetUser->createToken('impersonation', ['*'], $expiresAt);

        return [
            'success' => true,
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'impersonator_id' => $admin->id,
        ];
    }

    /**
     * Stop impersonation.
     */
    public function stop(User $admin, User $impersonatedUser): void
    {
        activity()
            ->event('impersonation_stopped')
            ->causedBy($admin)
            ->performedOn($impersonatedUser)
            ->useLog('impersonation')
            ->log('Stopped impersonating user');

        // Revoke the impersonation token
        $impersonatedUser->tokens()
            ->where('name', 'impersonation')
            ->delete();
    }
}
