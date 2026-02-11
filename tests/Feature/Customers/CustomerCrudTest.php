<?php

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->user = User::factory()->create();
    $this->tenant = Tenant::factory()->create();
    $this->tenant->users()->attach($this->user, ['role' => 'owner']);
    setPermissionsTeamId($this->tenant->id);
    $this->user->assignRole('owner');
    $this->actingAs($this->user, 'sanctum');
});

it('lists customers', function (): void {
    Customer::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->getJson('/api/v1/customers')
        ->assertSuccessful();
});

it('creates a customers record', function (): void {
    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->postJson('/api/v1/customers', [
            'name' => 'Test Value',
            'status' => 'active',
        ])
        ->assertCreated();
});

it('shows a customers record', function (): void {
    $model = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->getJson("/api/v1/customers/{$model->id}")
        ->assertSuccessful();
});

it('deletes a customers record', function (): void {
    $model = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->deleteJson("/api/v1/customers/{$model->id}")
        ->assertNoContent();
});

it('logs activity on create', function (): void {
    $this->withHeader('X-Tenant-ID', (string) $this->tenant->id)
        ->postJson('/api/v1/customers', [
            'name' => 'Test Value',
            'status' => 'active',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Customer::class,
        'event' => 'created',
    ]);
});
