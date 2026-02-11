<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'plan_id' => Plan::factory(),
            'stripe_subscription_id' => 'sub_'.fake()->unique()->bothify('??????????'),
            'stripe_customer_id' => 'cus_'.fake()->unique()->bothify('??????????'),
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'current_period_end' => now()->addMonth(),
            'grace_period_ends_at' => null,
            'canceled_at' => null,
        ];
    }

    public function trialing(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::Trialing,
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function active(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::Active,
        ]);
    }

    public function pastDue(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::PastDue,
        ]);
    }

    public function pastDueInGrace(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::PastDue,
            'grace_period_ends_at' => now()->addDays(7),
        ]);
    }

    public function pastDueGraceExpired(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::PastDue,
            'grace_period_ends_at' => now()->subDay(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
        ]);
    }

    public function unpaid(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::Unpaid,
        ]);
    }
}
