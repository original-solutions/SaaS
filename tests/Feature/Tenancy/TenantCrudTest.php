<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MembershipService;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->user = User::factory()->create();
    $this->membershipService = app(MembershipService::class);
});

it('lists user tenants', function (): void {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $this->membershipService->addMember($tenant1, $this->user, TenantRole::Owner);
    $this->membershipService->addMember($tenant2, $this->user, TenantRole::Member);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/tenants');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

it('creates a new tenant and user becomes owner', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/tenants', [
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

    $response->assertCreated();
    expect($response->json('data.name'))->toBe('Acme Corp');
    expect($response->json('data.slug'))->toBe('acme-corp');

    // User should be owner
    $tenant = Tenant::where('slug', 'acme-corp')->first();
    $pivot = $tenant->users()->where('users.id', $this->user->id)->first();
    expect($pivot->pivot->role)->toBe('owner');
});

it('shows a tenant the user is a member of', function (): void {
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $this->user, TenantRole::Member);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/v1/tenants/{$tenant->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $tenant->id);
});

it('returns 403 for non-member trying to view tenant', function (): void {
    $tenant = Tenant::factory()->create();

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/v1/tenants/{$tenant->id}");

    $response->assertForbidden();
});

it('updates a tenant', function (): void {
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $this->user, TenantRole::Owner);

    $response = $this->actingAs($this->user, 'sanctum')
        ->putJson("/api/v1/tenants/{$tenant->id}", [
            'name' => 'New Name',
        ]);

    $response->assertSuccessful();
    expect($response->json('data.name'))->toBe('New Name');
});

it('soft deletes a tenant', function (): void {
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $this->user, TenantRole::Owner);

    $response = $this->actingAs($this->user, 'sanctum')
        ->deleteJson("/api/v1/tenants/{$tenant->id}");

    $response->assertSuccessful();
    expect(Tenant::find($tenant->id))->toBeNull();
    expect(Tenant::withTrashed()->find($tenant->id))->not->toBeNull();
});

it('non-owner cannot delete tenant', function (): void {
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $this->user, TenantRole::Admin);

    $response = $this->actingAs($this->user, 'sanctum')
        ->deleteJson("/api/v1/tenants/{$tenant->id}");

    $response->assertForbidden();
});

it('validates tenant creation fields', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/tenants', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'slug']);
});

it('rejects duplicate slug', function (): void {
    Tenant::factory()->create(['slug' => 'acme-corp']);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/tenants', [
            'name' => 'Acme Corp 2',
            'slug' => 'acme-corp',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});

it('requires authentication', function (): void {
    $this->getJson('/api/v1/tenants')->assertUnauthorized();
    $this->postJson('/api/v1/tenants', ['name' => 'Test', 'slug' => 'test'])->assertUnauthorized();
});
