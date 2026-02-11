<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Services\MembershipService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->membershipService = app(MembershipService::class);
});

it('allows owner to update role on pending invite', function () {
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $owner, TenantRole::Owner);

    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => TenantRole::Member,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/tenants/{$tenant->id}/invitations/{$invitation->id}", [
            'role' => 'admin',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.role', 'admin');
});

it('allows admin to update role on pending invite', function () {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $admin, TenantRole::Admin);

    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => TenantRole::Member,
    ]);

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/tenants/{$tenant->id}/invitations/{$invitation->id}", [
            'role' => 'readonly',
        ])
        ->assertSuccessful();
});

it('denies member from updating role on invite', function () {
    $member = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $member, TenantRole::Member);

    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $this->actingAs($member, 'sanctum')
        ->putJson("/api/v1/tenants/{$tenant->id}/invitations/{$invitation->id}", [
            'role' => 'admin',
        ])
        ->assertForbidden();
});

it('rejects role update on accepted invitation', function () {
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $owner, TenantRole::Owner);

    $invitation = TenantInvitation::factory()->accepted()->create([
        'tenant_id' => $tenant->id,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/tenants/{$tenant->id}/invitations/{$invitation->id}", [
            'role' => 'admin',
        ])
        ->assertStatus(409);
});
