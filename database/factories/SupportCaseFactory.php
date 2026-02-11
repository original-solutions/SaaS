<?php

namespace Database\Factories;

use App\Enums\SupportCasePriority;
use App\Enums\SupportCaseStatus;
use App\Models\SupportCase;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportCase>
 */
class SupportCaseFactory extends Factory
{
    protected $model = SupportCase::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'subject' => fake()->sentence(),
            'status' => SupportCaseStatus::Open,
            'priority' => fake()->randomElement(SupportCasePriority::cases()),
            'meta' => null,
            'created_by_user_id' => User::factory(),
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => SupportCaseStatus::Open]);
    }

    public function pending(): static
    {
        return $this->state(['status' => SupportCaseStatus::Pending]);
    }

    public function resolved(): static
    {
        return $this->state(['status' => SupportCaseStatus::Resolved]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => SupportCasePriority::High]);
    }
}
