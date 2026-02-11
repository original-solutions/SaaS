<?php

use App\Models\SavedView;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->tenant = Tenant::factory()->create();
    $this->tenant->users()->attach($this->user, ['role' => 'owner']);
    $this->actingAs($this->user, 'sanctum');
});

it('lists saved views for a resource type', function (): void {
    SavedView::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'resource_type' => 'customers',
    ]);

    SavedView::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'resource_type' => 'other',
    ]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->getJson('/api/v1/saved-views?resource_type=customers')
        ->assertSuccessful();
});

it('creates a saved view', function (): void {
    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->postJson('/api/v1/saved-views', [
            'resource_type' => 'customers',
            'name' => 'Active Customers',
            'config' => ['filters' => ['status' => 'active']],
        ])
        ->assertCreated();

    $this->assertDatabaseHas('saved_views', [
        'name' => 'Active Customers',
        'resource_type' => 'customers',
        'user_id' => $this->user->id,
    ]);
});

it('updates a saved view', function (): void {
    $view = SavedView::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
    ]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->putJson("/api/v1/saved-views/{$view->id}", [
            'name' => 'Updated View',
        ])
        ->assertSuccessful();

    expect($view->fresh()->name)->toBe('Updated View');
});

it('deletes a saved view', function (): void {
    $view = SavedView::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
    ]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->deleteJson("/api/v1/saved-views/{$view->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('saved_views', ['id' => $view->id]);
});

it('sets a view as default and unsets others', function (): void {
    $view1 = SavedView::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'resource_type' => 'customers',
        'is_default' => true,
    ]);

    $view2 = SavedView::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'resource_type' => 'customers',
        'is_default' => false,
    ]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->postJson("/api/v1/saved-views/{$view2->id}/default")
        ->assertSuccessful();

    expect($view1->fresh()->is_default)->toBeFalse();
    expect($view2->fresh()->is_default)->toBeTrue();
});
