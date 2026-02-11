<?php

namespace Database\Factories;

use App\Models\FeatureFlag;
use App\Models\FeatureFlagOverride;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeatureFlagOverride>
 */
class FeatureFlagOverrideFactory extends Factory
{
    protected $model = FeatureFlagOverride::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feature_flag_id' => FeatureFlag::factory(),
            'tenant_id' => null,
            'user_id' => null,
            'enabled' => true,
        ];
    }

    public function forTenant(?Tenant $tenant = null): static
    {
        return $this->state([
            'tenant_id' => $tenant?->id ?? Tenant::factory(),
        ]);
    }

    public function forUser(?User $user = null): static
    {
        return $this->state([
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    public function disabled(): static
    {
        return $this->state(['enabled' => false]);
    }
}
