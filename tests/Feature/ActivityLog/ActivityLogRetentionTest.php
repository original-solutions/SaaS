<?php

use App\Models\Activity;
use App\Models\Tenant;
use App\Models\User;

it('prunes old activity logs based on config retention days', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    // Create an old log
    $tenant = Tenant::factory()->create();
    $oldLog = Activity::create([
        'log_name' => 'default',
        'description' => 'Old activity',
        'subject_type' => Tenant::class,
        'subject_id' => $tenant->id,
        'causer_type' => User::class,
        'causer_id' => $user->id,
        'created_at' => now()->subDays(400),
    ]);

    // Create a recent log
    $recentLog = Activity::create([
        'log_name' => 'default',
        'description' => 'Recent activity',
        'subject_type' => Tenant::class,
        'subject_id' => $tenant->id,
        'causer_type' => User::class,
        'causer_id' => $user->id,
        'created_at' => now()->subDays(10),
    ]);

    // Clean up using Spatie's built-in command
    $this->artisan('activitylog:clean')
        ->assertExitCode(0);

    expect(Activity::find($oldLog->id))->toBeNull()
        ->and(Activity::find($recentLog->id))->not->toBeNull();
});
