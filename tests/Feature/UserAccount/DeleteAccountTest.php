<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MembershipService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->membershipService = app(MembershipService::class);
});

it('deletes account when user is not sole owner', function () {
    $user = User::factory()->create();
    $otherOwner = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Owner);
    $this->membershipService->addMember($tenant, $otherOwner, TenantRole::Owner);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/account', [
            'password' => 'password',
        ])
        ->assertSuccessful();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

it('blocks deletion when user is sole owner', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Owner);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/account', [
            'password' => 'password',
        ])
        ->assertStatus(409)
        ->assertJsonPath('error', 'SOLE_OWNER');
});

it('removes memberships on deletion', function () {
    $user = User::factory()->create();
    $otherOwner = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Member);
    $this->membershipService->addMember($tenant, $otherOwner, TenantRole::Owner);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/account', [
            'password' => 'password',
        ])
        ->assertSuccessful();

    $this->assertDatabaseMissing('tenant_user', ['user_id' => $user->id]);
});

it('anonymises activity log on delete with anonymise config', function () {
    config(['saas.content_retention_on_delete' => 'anonymise']);

    $user = User::factory()->create();

    // Create an activity log entry for this user
    DB::table('activity_log')->insert([
        'log_name' => 'default',
        'description' => 'test action',
        'causer_id' => $user->id,
        'causer_type' => User::class,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/account', [
            'password' => 'password',
        ])
        ->assertSuccessful();

    $log = DB::table('activity_log')->where('description', 'test action')->first();
    expect($log->causer_id)->toBeNull();
    expect($log->causer_type)->toBeNull();
});

it('retains user_id in activity log on delete with retain config', function () {
    config(['saas.content_retention_on_delete' => 'retain']);

    $user = User::factory()->create();
    $userId = $user->id;

    DB::table('activity_log')->insert([
        'log_name' => 'default',
        'description' => 'test retain action',
        'causer_id' => $userId,
        'causer_type' => User::class,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/account', [
            'password' => 'password',
        ])
        ->assertSuccessful();

    $log = DB::table('activity_log')->where('description', 'test retain action')->first();
    expect($log->causer_id)->toBe($userId);
});

it('removes all Spatie roles on deletion', function () {
    $user = User::factory()->create();
    $otherOwner = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $user, TenantRole::Member);
    $this->membershipService->addMember($tenant, $otherOwner, TenantRole::Owner);

    // Verify user has role before deletion
    setPermissionsTeamId($tenant->id);
    $user->unsetRelation('roles');
    expect($user->hasRole('member'))->toBeTrue();

    $userId = $user->id;

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/account', [
            'password' => 'password',
        ])
        ->assertSuccessful();

    // Verify roles are removed
    $this->assertDatabaseMissing('model_has_roles', ['model_id' => $userId]);
});
