<?php

use App\Models\User;
use App\Services\UserAccountService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->accountService = app(UserAccountService::class);
});

it('locks a user account', function () {
    $user = User::factory()->create();

    $this->accountService->lock($user);

    expect($user->fresh()->isLocked())->toBeTrue();
});

it('prevents locked user from logging in', function () {
    $user = User::factory()->create([
        'locked_at' => now(),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertForbidden();
});

it('unlocks a user account', function () {
    $user = User::factory()->create(['locked_at' => now()]);

    $this->accountService->unlock($user);

    expect($user->fresh()->isLocked())->toBeFalse();
});

it('unlocked user can log in', function () {
    $user = User::factory()->create([
        'locked_at' => now(),
    ]);

    $this->accountService->unlock($user);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSuccessful();
});
