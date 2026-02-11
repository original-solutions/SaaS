<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Services\MembershipService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->membershipService = app(MembershipService::class);
});

it('allows owner to send an invitation', function () {
    Notification::fake();

    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Owner);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/tenants/{$tenant->id}/invitations", [
            'email' => 'newuser@example.com',
            'role' => 'member',
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'newuser@example.com')
        ->assertJsonPath('data.role', 'member');

    $this->assertDatabaseHas('tenant_invitations', [
        'tenant_id' => $tenant->id,
        'email' => 'newuser@example.com',
        'role' => 'member',
    ]);
});

it('allows admin to send an invitation', function () {
    Notification::fake();

    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Admin);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/tenants/{$tenant->id}/invitations", [
            'email' => 'another@example.com',
            'role' => 'member',
        ])
        ->assertCreated();
});

it('denies member from sending invitation', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Member);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/tenants/{$tenant->id}/invitations", [
            'email' => 'another@example.com',
            'role' => 'member',
        ])
        ->assertForbidden();
});

it('denies readonly from sending invitation', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Readonly);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/tenants/{$tenant->id}/invitations", [
            'email' => 'another@example.com',
            'role' => 'member',
        ])
        ->assertForbidden();
});

it('resends instead of creating duplicate pending invite', function () {
    Notification::fake();

    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Owner);

    // First invite
    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/tenants/{$tenant->id}/invitations", [
            'email' => 'dup@example.com',
            'role' => 'member',
        ])
        ->assertCreated();

    $originalToken = TenantInvitation::where('email', 'dup@example.com')->first()->token;

    // Second invite — should resend (new token), not create new record
    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/tenants/{$tenant->id}/invitations", [
            'email' => 'dup@example.com',
            'role' => 'member',
        ])
        ->assertCreated();

    expect(TenantInvitation::where('email', 'dup@example.com')->count())->toBe(1);
    expect(TenantInvitation::where('email', 'dup@example.com')->first()->token)->not->toBe($originalToken);
});

it('validates invitation fields', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Owner);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/tenants/{$tenant->id}/invitations", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'role']);
});

it('validates role is a valid tenant role', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Owner);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/tenants/{$tenant->id}/invitations", [
            'email' => 'test@example.com',
            'role' => 'invalid-role',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});
