<?php

namespace Database\Factories;

use App\Models\DeviceSession;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RefreshToken>
 */
class RefreshTokenFactory extends Factory
{
    protected $model = RefreshToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_session_id' => DeviceSession::factory(),
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addMinutes(config('saas.refresh_token_ttl', 10080)),
        ];
    }

    /**
     * Mark the token as expired.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    /**
     * Mark the token as used.
     */
    public function used(): static
    {
        return $this->state(fn () => [
            'used_at' => now(),
        ]);
    }

    /**
     * Mark the token as revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn () => [
            'revoked_at' => now(),
        ]);
    }
}
