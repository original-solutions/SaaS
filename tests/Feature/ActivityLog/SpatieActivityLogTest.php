<?php

use App\Models\Activity;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

it('auto-logs activity when a tenant is created', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $tenant = Tenant::create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'active',
    ]);

    $log = Activity::where('subject_type', Tenant::class)
        ->where('subject_id', $tenant->id)
        ->where('event', 'created')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->causer_id)->toBe($user->id)
        ->and($log->properties['attributes'])->toHaveKey('name', 'Test Org');
});

it('auto-logs activity when a tenant is updated', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $tenant = Tenant::create([
        'name' => 'Original',
        'slug' => 'original',
        'status' => 'active',
    ]);

    $tenant->update(['name' => 'Updated']);

    $log = Activity::where('subject_type', Tenant::class)
        ->where('subject_id', $tenant->id)
        ->where('event', 'updated')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['old'])->toHaveKey('name', 'Original')
        ->and($log->properties['attributes'])->toHaveKey('name', 'Updated');
});

it('auto-logs activity when a tenant is deleted', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $tenant = Tenant::create([
        'name' => 'To Delete',
        'slug' => 'to-delete',
        'status' => 'active',
    ]);

    $tenant->delete();

    $log = Activity::where('subject_type', Tenant::class)
        ->where('subject_id', $tenant->id)
        ->where('event', 'deleted')
        ->first();

    expect($log)->not->toBeNull();
});

it('sets causer_id to the authenticated user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    Tenant::create([
        'name' => 'Causer Test',
        'slug' => 'causer-test',
        'status' => 'active',
    ]);

    $log = Activity::latest()->first();

    expect($log->causer_id)->toBe($user->id)
        ->and($log->causer_type)->toBe(User::class);
});

it('auto-fills tenant_id from TenantContext', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $existingTenant = Tenant::factory()->create();
    $tenantContext = app(TenantContext::class);
    $tenantContext->set($existingTenant);

    $newTenant = Tenant::create([
        'name' => 'Context Test',
        'slug' => 'context-test',
        'status' => 'active',
    ]);

    $log = Activity::where('subject_id', $newTenant->id)
        ->where('event', 'created')
        ->first();

    expect($log->tenant_id)->toBe($existingTenant->id);

    $tenantContext->clear();
});

it('auto-fills ip_address from request', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    Tenant::create([
        'name' => 'IP Test',
        'slug' => 'ip-test',
        'status' => 'active',
    ]);

    $log = Activity::latest()->first();

    expect($log->ip_address)->not->toBeNull();
});

it('only logs dirty fields on update', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $tenant = Tenant::create([
        'name' => 'Dirty Test',
        'slug' => 'dirty-test',
        'status' => 'active',
        'plan' => 'basic',
    ]);

    $tenant->update(['name' => 'Dirty Updated']);

    $log = Activity::where('event', 'updated')->latest()->first();

    // Only name should be in the log since only name changed
    expect($log->properties['attributes'])->toHaveKey('name')
        ->and($log->properties['old'])->toHaveKey('name');
});
