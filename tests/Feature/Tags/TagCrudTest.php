<?php

use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create();
    $this->tenant->users()->attach($this->user, ['role' => 'owner', 'joined_at' => now()]);

    app(TenantContext::class)->set($this->tenant);
});

it('creates a tag', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->postJson('/api/v1/tags', [
            'name' => 'Important',
            'color' => '#ff0000',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Important');

    $this->assertDatabaseHas('tags', [
        'tenant_id' => $this->tenant->id,
        'name' => 'Important',
    ]);
});

it('lists tags for the tenant', function (): void {
    Tag::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->getJson('/api/v1/tags');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('updates a tag', function (): void {
    $tag = Tag::factory()->create(['tenant_id' => $this->tenant->id]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->putJson("/api/v1/tags/{$tag->id}", [
            'name' => 'Updated Tag',
        ]);

    $response->assertSuccessful();
    expect($tag->fresh()->name)->toBe('Updated Tag');
});

it('deletes a tag', function (): void {
    $tag = Tag::factory()->create(['tenant_id' => $this->tenant->id]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->deleteJson("/api/v1/tags/{$tag->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});

it('tenant isolates tags', function (): void {
    $otherTenant = Tenant::factory()->create();

    Tag::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);

    // Clear context to create tags for other tenant
    app(TenantContext::class)->clear();
    app(TenantContext::class)->set($otherTenant);
    Tag::factory()->count(3)->create(['tenant_id' => $otherTenant->id]);

    // Querying within otherTenant context should only show 3
    expect(Tag::count())->toBe(3);

    // Switch back and should see only 2
    app(TenantContext::class)->clear();
    app(TenantContext::class)->set($this->tenant);
    expect(Tag::count())->toBe(2);
});
