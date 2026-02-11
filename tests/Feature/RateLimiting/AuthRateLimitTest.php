<?php

use App\Models\User;

it('rate limits login attempts per IP', function (): void {
    // 10 attempts per minute per IP
    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => "user{$i}@example.com",
            'password' => 'wrong',
        ]);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => 'user11@example.com',
        'password' => 'wrong',
    ])->assertStatus(429);
});

it('rate limits login attempts per email', function (): void {
    $email = 'target@example.com';

    // 5 attempts per minute per email
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'wrong',
        ]);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => $email,
        'password' => 'wrong',
    ])->assertStatus(429);
});

it('rate limits logout per user', function (): void {
    $user = User::factory()->create();

    // 30 attempts per minute per user
    for ($i = 0; $i < 30; $i++) {
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/logout');
    }

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/logout')
        ->assertStatus(429);
});
