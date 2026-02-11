<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    protected $model = File::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'disk' => 'local',
            'path' => 'tenants/1/files/'.fake()->uuid().'.txt',
            'original_name' => fake()->word().'.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => fake()->numberBetween(100, 10000),
            'uploaded_by_user_id' => User::factory(),
        ];
    }
}
