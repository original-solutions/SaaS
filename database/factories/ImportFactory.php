<?php

namespace Database\Factories;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Import>
 */
class ImportFactory extends Factory
{
    protected $model = Import::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'type' => 'csv',
            'status' => ImportStatus::Queued,
            'progress' => 0,
            'total_rows' => fake()->numberBetween(10, 1000),
            'processed_rows' => 0,
            'error_count' => 0,
            'input_file_id' => null,
            'result_file_id' => null,
            'meta' => [],
            'created_by_user_id' => User::factory(),
        ];
    }

    public function running(): static
    {
        return $this->state([
            'status' => ImportStatus::Running,
            'progress' => fake()->numberBetween(1, 99),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => ImportStatus::Completed,
            'progress' => 100,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => ImportStatus::Failed,
        ]);
    }
}
