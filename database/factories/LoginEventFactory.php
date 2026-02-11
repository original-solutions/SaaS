<?php

namespace Database\Factories;

use App\Enums\LoginEventType;
use App\Models\LoginEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoginEvent>
 */
class LoginEventFactory extends Factory
{
    protected $model = LoginEvent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'email' => fake()->safeEmail(),
            'event_type' => LoginEventType::LoginSuccess,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
