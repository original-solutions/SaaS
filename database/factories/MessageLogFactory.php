<?php

namespace Database\Factories;

use App\Models\MessageLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageLog>
 */
class MessageLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'channel' => 'email',
            'recipient' => fake()->safeEmail(),
            'subject' => fake()->sentence(),
            'status' => fake()->randomElement(['sent', 'delivered', 'bounced', 'failed']),
            'provider_message_id' => fake()->optional()->uuid(),
        ];
    }
}
