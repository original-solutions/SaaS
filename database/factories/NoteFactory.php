<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'author_user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'noteable_type' => null,
            'noteable_id' => null,
        ];
    }
}
