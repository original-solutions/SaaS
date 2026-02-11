<?php

namespace Database\Factories;

use App\Enums\WebhookEventStatus;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => fake()->randomElement(['stripe', 'github', 'slack']),
            'event_id' => (string) Str::uuid(),
            'type' => fake()->randomElement(['payment.completed', 'subscription.updated', 'user.created']),
            'payload' => ['key' => fake()->word()],
            'received_at' => now(),
            'processed_at' => null,
            'status' => WebhookEventStatus::Received,
            'attempts' => 0,
            'last_error' => null,
            'idempotency_key' => (string) Str::uuid(),
        ];
    }

    public function processed(): static
    {
        return $this->state([
            'status' => WebhookEventStatus::Processed,
            'processed_at' => now(),
            'attempts' => 1,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => WebhookEventStatus::Failed,
            'attempts' => 3,
            'last_error' => 'Processing failed after max retries.',
        ]);
    }
}
