<?php

namespace App\Services;

use App\Models\MagicLoginToken;
use App\Models\User;
use Illuminate\Support\Str;

class MagicLinkService
{
    /**
     * Generate a magic login token and return the plain token.
     */
    public function generate(string $email): ?string
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return null;
        }

        $plainToken = Str::random(64);

        MagicLoginToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes((int) config('saas.magic_link_ttl', 15)),
        ]);

        // TODO: Send email with magic link in Phase 7 (Notifications)

        return $plainToken;
    }

    /**
     * Verify a magic login token and return the user.
     */
    public function verify(string $plainToken): ?User
    {
        $tokenHash = hash('sha256', $plainToken);
        $magicToken = MagicLoginToken::where('token_hash', $tokenHash)->first();

        if (! $magicToken) {
            return null;
        }

        if ($magicToken->isExpired() || $magicToken->isUsed()) {
            return null;
        }

        // Mark as used
        $magicToken->update(['used_at' => now()]);

        return $magicToken->user;
    }
}
