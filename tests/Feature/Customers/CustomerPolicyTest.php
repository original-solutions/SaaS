<?php

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->tenant = Tenant::factory()->create();
});

it('allows owner full CRUD access', function (): void {
    $user = User::factory()->create();
    $this->tenant->users()->attach($user, ['role' => 'owner']);
    setPermissionsTeamId($this->tenant->id);
    $user->assignRole('owner');
    $this->actingAs($user, 'sanctum');

    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

    // Create
    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->postJson('/api/v1/customers', ['name' => 'New Customer', 'status' => 'active'])
        ->assertCreated();

    // Update
    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->putJson("/api/v1/customers/{$customer->id}", ['name' => 'Updated'])
        ->assertSuccessful();

    // Delete
    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->deleteJson("/api/v1/customers/{$customer->id}")
        ->assertNoContent();
});

it('allows member to read and create', function (): void {
    $user = User::factory()->create();
    $this->tenant->users()->attach($user, ['role' => 'member']);
    setPermissionsTeamId($this->tenant->id);
    $user->assignRole('member');
    $this->actingAs($user, 'sanctum');

    // List
    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->getJson('/api/v1/customers')
        ->assertSuccessful();

    // Create
    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->postJson('/api/v1/customers', ['name' => 'New Customer', 'status' => 'active'])
        ->assertCreated();
});

it('allows readonly to only read', function (): void {
    $user = User::factory()->create();
    $this->tenant->users()->attach($user, ['role' => 'readonly']);
    setPermissionsTeamId($this->tenant->id);
    $user->assignRole('readonly');
    $this->actingAs($user, 'sanctum');

    // List
    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->getJson('/api/v1/customers')
        ->assertSuccessful();

    // Create should be forbidden
    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->postJson('/api/v1/customers', ['name' => 'New Customer', 'status' => 'active'])
        ->assertForbidden();
});
