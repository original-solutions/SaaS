<?php

use App\Models\AdminNote;
use App\Models\User;

it('lists admin notes', function (): void {
    $admin = User::factory()->create(['is_super_admin' => true]);
    AdminNote::factory()->count(3)->createdBy($admin)->create();
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/notes')
        ->assertSuccessful()
        ->assertJsonPath('total', 3);
});

it('filters admin notes by target', function (): void {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $targetUser = User::factory()->create();
    AdminNote::factory()->forUser($targetUser)->createdBy($admin)->create();
    AdminNote::factory()->createdBy($admin)->create();
    actingAsSuperAdmin();

    $this->getJson("/admin/api/v1/notes?target_type=user&target_id={$targetUser->id}")
        ->assertSuccessful()
        ->assertJsonPath('total', 1);
});

it('creates an admin note', function (): void {
    $targetUser = User::factory()->create();
    actingAsSuperAdmin();

    $this->postJson('/admin/api/v1/notes', [
        'target_type' => 'user',
        'target_id' => $targetUser->id,
        'note' => 'Test admin note.',
    ])->assertCreated()
        ->assertJsonPath('data.note', 'Test admin note.');
});

it('validates required fields when creating an admin note', function (): void {
    actingAsSuperAdmin();

    $this->postJson('/admin/api/v1/notes', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['target_type', 'target_id', 'note']);
});

it('updates an admin note', function (): void {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $note = AdminNote::factory()->createdBy($admin)->create();
    actingAsSuperAdmin();

    $this->putJson("/admin/api/v1/notes/{$note->id}", [
        'note' => 'Updated note.',
    ])->assertSuccessful()
        ->assertJsonPath('data.note', 'Updated note.');
});

it('deletes an admin note', function (): void {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $note = AdminNote::factory()->createdBy($admin)->create();
    actingAsSuperAdmin();

    $this->deleteJson("/admin/api/v1/notes/{$note->id}")
        ->assertNoContent();

    expect(AdminNote::find($note->id))->toBeNull();
});
