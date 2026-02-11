<?php

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'status' => TenantStatus::Active,
        ];
    }

    /**
     * Indicate that the tenant is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantStatus::Disabled,
            'disabled_at' => now(),
        ]);
    }

    /**
     * Indicate that the tenant is soft deleted.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantStatus::Deleted,
            'deleted_at' => now(),
        ]);
    }

    /**
     * Indicate that the tenant is on a trial.
     */
    public function trialing(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->addDays(14),
        ]);
    }
}
