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

it('resending generates new token and resets expiry', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $owner, TenantRole::Owner);

    // Create invitation with an expiry in the past relative to the resend
    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'invited_by_user_id' => $owner->id,
        'expires_at' => now()->addDays(2), // Shorter expiry
    ]);
    $originalToken = $invitation->token;
    $originalExpiry = $invitation->expires_at->copy();

    // Travel forward so resend creates a later expiry
    $this->travel(1)->days();

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/tenants/{$tenant->id}/invitations/{$invitation->id}/resend")
        ->assertSuccessful();

    $invitation->refresh();
    expect($invitation->token)->not->toBe($originalToken);
    expect($invitation->expires_at->isAfter($originalExpiry))->toBeTrue();
});

it('rejects resending non-pending invitation', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $owner, TenantRole::Owner);

    $invitation = TenantInvitation::factory()->accepted()->create([
        'tenant_id' => $tenant->id,
        'invited_by_user_id' => $owner->id,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/tenants/{$tenant->id}/invitations/{$invitation->id}/resend")
        ->assertStatus(409);
});
