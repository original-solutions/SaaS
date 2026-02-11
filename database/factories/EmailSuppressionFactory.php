<?php

namespace Database\Factories;

use App\Models\EmailSuppression;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailSuppression>
 */
class EmailSuppressionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'reason' => fake()->randomElement(['hard_bounce', 'complaint', 'manual']),
            'details' => fake()->optional()->sentence(),
        ];
    }
}
