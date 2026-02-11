<?php

use App\Models\MagicLoginToken;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;

it('prunes expired invitations', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    // Expired invitation
    TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'invited_by_user_id' => $user->id,
        'expires_at' => now()->subDay(),
        'accepted_at' => null,
    ]);

    // Valid invitation
    TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'invited_by_user_id' => $user->id,
        'expires_at' => now()->addDay(),
        'accepted_at' => null,
    ]);

    // Run the cleanup
    TenantInvitation::whereNull('accepted_at')
        ->where('expires_at', '<', now())
        ->delete();

    expect(TenantInvitation::count())->toBe(1);
});

it('prunes expired magic login tokens', function (): void {
    $user = User::factory()->create();

    MagicLoginToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', \Illuminate\Support\Str::random(64)),
        'expires_at' => now()->subHour(),
    ]);

    MagicLoginToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', \Illuminate\Support\Str::random(64)),
        'expires_at' => now()->addHour(),
    ]);

    MagicLoginToken::where('expires_at', '<', now())->delete();

    expect(MagicLoginToken::count())->toBe(1);
});

it('prunes old activity log entries', function (): void {
    // Activity log with old entry
    \App\Models\Activity::create([
        'log_name' => 'default',
        'description' => 'old entry',
        'created_at' => now()->subDays(400),
    ]);

    \App\Models\Activity::create([
        'log_name' => 'default',
        'description' => 'recent entry',
        'created_at' => now()->subDays(30),
    ]);

    $days = config('saas.activity_log_retention_days', 365);
    \App\Models\Activity::where('created_at', '<', now()->subDays($days))->delete();

    expect(\App\Models\Activity::count())->toBe(1);
});
