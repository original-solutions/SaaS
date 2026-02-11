<?php

namespace App\Services;

use App\Enums\LoginEventType;
use App\Enums\RevocationReason;
use App\Models\DeviceSession;
use App\Models\LoginEvent;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Attempt login with credentials.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}|null
     */
    public function login(string $email, string $password, ?string $ip = null, ?string $userAgent = null): ?array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            $this->recordLoginEvent($email, null, LoginEventType::LoginFailed, $ip, $userAgent);

            return null;
        }

        if ($user->isLocked()) {
            $this->recordLoginEvent($email, $user, LoginEventType::LoginFailed, $ip, $userAgent, [
                'reason' => 'account_locked',
            ]);

            return null;
        }

        // Create device session
        $deviceSession = $user->deviceSessions()->create([
            'ip_address' => $ip,
            'created_ip' => $ip,
            'user_agent' => $userAgent,
            'last_active_at' => now(),
        ]);

        // Create tokens
        $tokens = $this->createTokenPair($user, $deviceSession);

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Record login event
        $this->recordLoginEvent($email, $user, LoginEventType::LoginSuccess, $ip, $userAgent);

        return $tokens;
    }

    /**
     * Refresh an access token using a refresh token.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}|null
     */
    public function refresh(string $refreshTokenPlain): ?array
    {
        $tokenHash = hash('sha256', $refreshTokenPlain);
        $refreshToken = RefreshToken::where('token_hash', $tokenHash)->first();

        if (! $refreshToken) {
            return null;
        }

        // Check if token has been used (reuse detection)
        if ($refreshToken->isUsed()) {
            $this->handleTokenReuse($refreshToken);

            return null;
        }

        if ($refreshToken->isExpired() || $refreshToken->isRevoked()) {
            return null;
        }

        // Mark current token as used
        $refreshToken->update(['used_at' => now()]);

        // Create new token pair
        return $this->createTokenPair(
            $refreshToken->user,
            $refreshToken->deviceSession,
        );
    }

    /**
     * Logout — revoke the current device session.
     */
    public function logout(User $user, ?string $refreshTokenPlain = null): void
    {
        if ($refreshTokenPlain) {
            $tokenHash = hash('sha256', $refreshTokenPlain);
            $refreshToken = RefreshToken::where('token_hash', $tokenHash)->first();

            if ($refreshToken) {
                $refreshToken->deviceSession->revoke(RevocationReason::Logout);
            }
        }

        // Revoke all Sanctum tokens for the user
        $user->currentAccessToken()?->delete();

        $this->recordLoginEvent($user->email, $user, LoginEventType::Logout, request()?->ip(), request()?->userAgent());
    }

    /**
     * Revoke all sessions and tokens for a user.
     */
    public function revokeAllForUser(User $user, RevocationReason $reason): void
    {
        $user->deviceSessions()->active()->each(fn (DeviceSession $session) => $session->revoke($reason));
        $user->tokens()->delete();
    }

    /**
     * Login a user directly (e.g., via magic link) — creates device session + token pair.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function loginViaUser(User $user, ?string $ip = null, ?string $userAgent = null): array
    {
        $deviceSession = $user->deviceSessions()->create([
            'ip_address' => $ip,
            'created_ip' => $ip,
            'user_agent' => $userAgent,
            'last_active_at' => now(),
        ]);

        $tokens = $this->createTokenPair($user, $deviceSession);

        $user->update(['last_login_at' => now()]);

        $this->recordLoginEvent($user->email, $user, LoginEventType::LoginSuccess, $ip, $userAgent, [
            'method' => 'magic_link',
        ]);

        return $tokens;
    }

    /**
     * Create an access + refresh token pair.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    protected function createTokenPair(User $user, DeviceSession $deviceSession): array
    {
        $accessTokenTtl = (int) config('saas.access_token_ttl', 15);
        $refreshTokenTtl = (int) config('saas.refresh_token_ttl', 10080);

        // Create Sanctum access token
        $accessToken = $user->createToken(
            'access-token',
            ['*'],
            now()->addMinutes($accessTokenTtl),
        );

        // Create refresh token
        $refreshTokenPlain = Str::random(64);
        $deviceSession->refreshTokens()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $refreshTokenPlain),
            'expires_at' => now()->addMinutes($refreshTokenTtl),
        ]);

        return [
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshTokenPlain,
            'expires_in' => $accessTokenTtl * 60, // seconds
        ];
    }

    /**
     * Handle refresh token reuse — possible token theft.
     */
    protected function handleTokenReuse(RefreshToken $refreshToken): void
    {
        $user = $refreshToken->user;

        // Revoke the device session chain
        $refreshToken->deviceSession->revoke(RevocationReason::RefreshReuseDetected);

        // Optionally revoke ALL sessions for this user
        if (config('saas.revoke_all_on_reuse', true)) {
            $this->revokeAllForUser($user, RevocationReason::RefreshReuseDetected);
        }

        // Log the reuse detection
        activity()
            ->causedBy($user)
            ->event('refresh_token_reuse_detected')
            ->withProperties([
                'device_session_id' => $refreshToken->device_session_id,
                'refresh_token_id' => $refreshToken->id,
            ])
            ->log('Refresh token reuse detected — possible token theft');
    }

    /**
     * Record a login event.
     */
    protected function recordLoginEvent(
        string $email,
        ?User $user,
        LoginEventType $type,
        ?string $ip,
        ?string $userAgent,
        array $metadata = [],
    ): void {
        LoginEvent::create([
            'user_id' => $user?->id,
            'email' => $email,
            'event_type' => $type,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'metadata' => $metadata ?: null,
        ]);
    }
}
