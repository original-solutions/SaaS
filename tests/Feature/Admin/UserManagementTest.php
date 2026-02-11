<?php

use App\Models\Tenant;
use App\Models\User;

it('lists all users', function (): void {
    User::factory()->count(3)->create();
    actingAsSuperAdmin();

    // +1 for the super admin user itself
    $this->getJson('/admin/api/v1/users')
        ->assertSuccessful()
        ->assertJsonPath('total', 4);
});

it('filters users by search term', function (): void {
    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/users?search=John')
        ->assertSuccessful()
        ->assertJsonPath('total', 1);
});

it('filters locked users', function (): void {
    User::factory()->create(['locked_at' => now()]);
    User::factory()->create(['locked_at' => null]);
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/users?locked=1')
        ->assertSuccessful()
        ->assertJsonPath('total', 1);
});

it('shows user details with relationships', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($user, ['role' => 'member']);
    actingAsSuperAdmin();

    $this->getJson("/admin/api/v1/users/{$user->id}")
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['tenants']]);
});

it('locks a user', function (): void {
    $user = User::factory()->create(['locked_at' => null]);
    actingAsSuperAdmin();

    $this->postJson("/admin/api/v1/users/{$user->id}/lock")
        ->assertSuccessful();

    expect($user->fresh()->locked_at)->not->toBeNull();
});

it('unlocks a user', function (): void {
    $user = User::factory()->create(['locked_at' => now()]);
    actingAsSuperAdmin();

    $this->postJson("/admin/api/v1/users/{$user->id}/unlock")
        ->assertSuccessful();

    expect($user->fresh()->locked_at)->toBeNull();
});

it('resets a user password', function (): void {
    $user = User::factory()->create();
    actingAsSuperAdmin();

    $response = $this->postJson("/admin/api/v1/users/{$user->id}/reset-password")
        ->assertSuccessful()
        ->assertJsonStructure(['password']);

    expect($response->json('password'))->toHaveLength(16);
});

it('revokes all user sessions', function (): void {
    $user = User::factory()->create();
    actingAsSuperAdmin();

    $this->postJson("/admin/api/v1/users/{$user->id}/revoke-sessions")
        ->assertSuccessful();
});
