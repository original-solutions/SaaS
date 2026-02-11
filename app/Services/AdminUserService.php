<?php

namespace App\Services;

use App\Enums\RevocationReason;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserService
{
    public function __construct(protected AuthService $authService) {}

    /**
     * Lock a user account.
     */
    public function lock(User $user): void
    {
        $user->update(['locked_at' => now()]);
    }

    /**
     * Unlock a user account.
     */
    public function unlock(User $user): void
    {
        $user->update(['locked_at' => null]);
    }

    /**
     * Reset a user's password to a random value.
     */
    public function resetPassword(User $user): string
    {
        $newPassword = Str::random(16);
        $user->update(['password' => Hash::make($newPassword)]);
        $this->authService->revokeAllForUser($user, RevocationReason::PasswordReset);

        return $newPassword;
    }

    /**
     * Revoke all sessions for a user.
     */
    public function revokeSessions(User $user): void
    {
        $this->authService->revokeAllForUser($user, RevocationReason::AdminRevoked);
    }
}
