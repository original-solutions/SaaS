<?php

use App\Models\User;

it('starts impersonation of a regular user', function (): void {
    $targetUser = User::factory()->create(['is_super_admin' => false]);
    actingAsSuperAdmin();

    $this->postJson("/admin/api/v1/impersonate/{$targetUser->id}/start", [
        'reason' => 'Debugging user issue',
    ])->assertSuccessful()
        ->assertJsonStructure(['token', 'expires_at', 'impersonator_id']);
});

it('blocks impersonation of another super admin', function (): void {
    $otherAdmin = User::factory()->create(['is_super_admin' => true]);
    actingAsSuperAdmin();

    $this->postJson("/admin/api/v1/impersonate/{$otherAdmin->id}/start")
        ->assertForbidden()
        ->assertJsonPath('error', 'CANNOT_IMPERSONATE_SUPER_ADMIN');
});

it('stops impersonation', function (): void {
    $targetUser = User::factory()->create(['is_super_admin' => false]);
    actingAsSuperAdmin();

    // Start impersonation first
    $this->postJson("/admin/api/v1/impersonate/{$targetUser->id}/start")
        ->assertSuccessful();

    $this->postJson("/admin/api/v1/impersonate/{$targetUser->id}/stop")
        ->assertSuccessful();
});

it('logs impersonation start activity', function (): void {
    $targetUser = User::factory()->create(['is_super_admin' => false]);
    actingAsSuperAdmin();

    $this->postJson("/admin/api/v1/impersonate/{$targetUser->id}/start")
        ->assertSuccessful();

    $this->assertDatabaseHas('activity_log', [
        'event' => 'impersonation_started',
        'subject_type' => User::class,
        'subject_id' => $targetUser->id,
    ]);
});
