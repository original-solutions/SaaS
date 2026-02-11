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

it('accepts a valid invitation', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);
    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => $user->email,
        'role' => TenantRole::Member,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/invitations/{$invitation->token}/accept")
        ->assertSuccessful();

    $this->assertDatabaseHas('tenant_invitations', [
        'id' => $invitation->id,
        'accepted_at' => now()->toDateTimeString(),
    ]);

    // User should now be a member of the tenant
    expect($user->tenants()->where('tenants.id', $tenant->id)->exists())->toBeTrue();
});

it('rejects expired invitation', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $invitation = TenantInvitation::factory()->expired()->create([
        'email' => $user->email,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/invitations/{$invitation->token}/accept")
        ->assertStatus(410)
        ->assertJsonPath('error', 'INVITE_EXPIRED');
});

it('rejects already accepted invitation', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $invitation = TenantInvitation::factory()->accepted()->create([
        'email' => $user->email,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/invitations/{$invitation->token}/accept")
        ->assertStatus(409)
        ->assertJsonPath('error', 'INVITE_ALREADY_ACCEPTED');
});

it('returns INVITE_EMAIL_MISMATCH when logged-in with different email', function () {
    $user = User::factory()->create([
        'email' => 'wrong@example.com',
        'email_verified_at' => now(),
    ]);
    $invitation = TenantInvitation::factory()->create([
        'email' => 'correct@example.com',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/invitations/{$invitation->token}/accept")
        ->assertStatus(403)
        ->assertJsonPath('error', 'INVITE_EMAIL_MISMATCH');

    expect($response->json('expected_email'))->toContain('*');
});

it('requires verified email before accepting', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $invitation = TenantInvitation::factory()->create([
        'email' => $user->email,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/invitations/{$invitation->token}/accept")
        ->assertStatus(403)
        ->assertJsonPath('error', 'EMAIL_NOT_VERIFIED');
});

it('assigns correct Spatie role on accept', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);
    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => $user->email,
        'role' => TenantRole::Admin,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/invitations/{$invitation->token}/accept")
        ->assertSuccessful();

    setPermissionsTeamId($tenant->id);
    $user->unsetRelation('roles');
    expect($user->hasRole('admin'))->toBeTrue();
});

it('handles existing user accepting invite and gaining membership', function () {
    $tenant = Tenant::factory()->create();
    $existingUser = User::factory()->create(['email_verified_at' => now()]);
    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => $existingUser->email,
        'role' => TenantRole::Member,
    ]);

    // User has no membership yet
    expect($existingUser->tenants()->count())->toBe(0);

    $this->actingAs($existingUser, 'sanctum')
        ->postJson("/api/v1/invitations/{$invitation->token}/accept")
        ->assertSuccessful();

    expect($existingUser->tenants()->count())->toBe(1);
});

it('declines a pending invitation', function () {
    $user = User::factory()->create();
    $invitation = TenantInvitation::factory()->create([
        'email' => $user->email,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/invitations/{$invitation->token}/decline")
        ->assertSuccessful();

    expect($invitation->fresh()->isDeclined())->toBeTrue();
});
