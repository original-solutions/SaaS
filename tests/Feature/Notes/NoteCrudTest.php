<?php

use App\Models\Note;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create();
    $this->tenant->users()->attach($this->user, ['role' => 'owner', 'joined_at' => now()]);

    // Set tenant context for BelongsToTenant trait
    app(TenantContext::class)->set($this->tenant);
});

it('creates a note', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->postJson('/api/v1/notes', [
            'body' => '<p>This is a <b>test</b> note.</p>',
            'noteable_type' => Tenant::class,
            'noteable_id' => $this->tenant->id,
        ]);

    $response->assertCreated();

    $this->assertDatabaseHas('notes', [
        'tenant_id' => $this->tenant->id,
        'author_user_id' => $this->user->id,
    ]);
});

it('lists notes for a noteable', function (): void {
    Note::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id,
        'author_user_id' => $this->user->id,
        'noteable_type' => Tenant::class,
        'noteable_id' => $this->tenant->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->getJson('/api/v1/notes?'.http_build_query([
            'noteable_type' => Tenant::class,
            'noteable_id' => $this->tenant->id,
        ]));

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('updates a note', function (): void {
    $note = Note::factory()->create([
        'tenant_id' => $this->tenant->id,
        'author_user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->putJson("/api/v1/notes/{$note->id}", [
            'body' => '<p>Updated note body</p>',
        ]);

    $response->assertSuccessful();
    expect($note->fresh()->body)->toBe('<p>Updated note body</p>');
});

it('deletes a note', function (): void {
    $note = Note::factory()->create([
        'tenant_id' => $this->tenant->id,
        'author_user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->deleteJson("/api/v1/notes/{$note->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('notes', ['id' => $note->id]);
});

it('sanitises HTML in note body', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->postJson('/api/v1/notes', [
            'body' => '<p>Hello</p><script>alert("xss")</script>',
            'noteable_type' => Tenant::class,
            'noteable_id' => $this->tenant->id,
        ]);

    // Safe HTML rule should reject this
    $response->assertUnprocessable();
});

it('rejects script tags via SafeHtml rule', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->postJson('/api/v1/notes', [
            'body' => '<script>alert("xss")</script>',
            'noteable_type' => Tenant::class,
            'noteable_id' => $this->tenant->id,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('body');
});
