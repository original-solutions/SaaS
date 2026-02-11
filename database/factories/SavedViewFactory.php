<?php

namespace Database\Factories;

use App\Models\SavedView;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedView>
 */
class SavedViewFactory extends Factory
{
    protected $model = SavedView::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'resource_type' => 'customers',
            'name' => fake()->words(2, true),
            'config' => ['filters' => [], 'sort' => 'created_at', 'columns' => ['name', 'email']],
            'is_default' => false,
        ];
    }
}
