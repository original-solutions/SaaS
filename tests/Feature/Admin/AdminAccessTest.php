<?php

use App\Models\User;

it('blocks non-super-admins from admin routes', function (): void {
    $user = User::factory()->create(['is_super_admin' => false]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/admin/api/v1/dashboard')
        ->assertForbidden();
});

it('allows super-admins to access admin routes', function (): void {
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/dashboard')
        ->assertSuccessful();
});

it('blocks unauthenticated users', function (): void {
    $this->getJson('/admin/api/v1/dashboard')
        ->assertUnauthorized();
});
