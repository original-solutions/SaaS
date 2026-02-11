<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserAccountService
{
    public function __construct(
        protected AuthService $authService,
        protected MembershipService $membershipService,
    ) {}

    /**
     * Export user data: profile, memberships, audit events.
     *
     * @return array<string, mixed>
     */
    public function exportData(User $user): array
    {
        return [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'memberships' => $user->tenants()->withPivot('role', 'joined_at')->get()->map(fn ($tenant) => [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'role' => $tenant->pivot->role,
                'joined_at' => $tenant->pivot->joined_at,
            ])->toArray(),
            'activity_log' => DB::table('activity_log')
                ->where('causer_id', $user->id)
                ->where('causer_type', User::class)
                ->orderByDesc('created_at')
                ->limit(1000)
                ->get()
                ->toArray(),
        ];
    }

    /**
     * Delete a user account with all safety checks.
     *
     * @return array{success: bool, error?: string}
     */
    public function deleteAccount(User $user): array
    {
        // Check sole owner constraint
        $ownedTenants = $user->tenants()
            ->wherePivot('role', 'owner')
            ->get();

        foreach ($ownedTenants as $tenant) {
            if ($this->membershipService->isSoleOwner($tenant, $user)) {
                return [
                    'success' => false,
                    'error' => 'SOLE_OWNER',
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                ];
            }
        }

        return DB::transaction(function () use ($user) {
            // Apply content retention policy
            $retentionPolicy = config('saas.content_retention_on_delete', 'anonymise');

            if ($retentionPolicy === 'anonymise') {
                // Anonymise actor fields in activity log
                DB::table('activity_log')
                    ->where('causer_id', $user->id)
                    ->where('causer_type', User::class)
                    ->update(['causer_id' => null, 'causer_type' => null]);
            }
            // 'retain' → keep user_id intact for audit purposes

            // Remove all Spatie role assignments
            $user->roles()->detach();

            // Remove all tenant memberships
            $user->tenants()->detach();

            // Revoke all tokens and sessions
            $this->authService->revokeAllForUser($user);

            // Delete the user
            $user->delete();

            return ['success' => true];
        });
    }

    /**
     * Change user email — triggers re-verification and optionally revokes tokens.
     */
    public function changeEmail(User $user, string $newEmail): void
    {
        $user->update([
            'email' => $newEmail,
            'email_verified_at' => null,
        ]);

        // Revoke all tokens for security
        $this->authService->revokeAllForUser($user);

        // Send verification email
        $user->sendEmailVerificationNotification();
    }

    /**
     * Lock a user account — prevents login.
     */
    public function lock(User $user): void
    {
        $user->update(['locked_at' => now()]);
        $this->authService->revokeAllForUser($user);
    }

    /**
     * Unlock a user account.
     */
    public function unlock(User $user): void
    {
        $user->update(['locked_at' => null]);
    }
}
