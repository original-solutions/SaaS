<?php

namespace Database\Factories;

use App\Enums\AdminNoteTargetType;
use App\Models\AdminNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminNote>
 */
class AdminNoteFactory extends Factory
{
    protected $model = AdminNote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'target_type' => fake()->randomElement(AdminNoteTargetType::cases()),
            'target_id' => User::factory(),
            'note' => fake()->paragraph(),
            'created_by_user_id' => User::factory(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state([
            'target_type' => AdminNoteTargetType::User,
            'target_id' => $user->id,
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state([
            'created_by_user_id' => $user->id,
        ]);
    }
}
