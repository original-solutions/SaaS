<?php

namespace Database\Factories;

use App\Models\DeviceSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceSession>
 */
class DeviceSessionFactory extends Factory
{
    protected $model = DeviceSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ip_address' => fake()->ipv4(),
            'created_ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'device_fingerprint' => fake()->sha256(),
            'last_active_at' => now(),
        ];
    }

    /**
     * Mark the session as revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn () => [
            'revoked_at' => now(),
            'revocation_reason' => 'logout',
        ]);
    }
}
