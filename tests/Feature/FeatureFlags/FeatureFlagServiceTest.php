<?php

use App\Models\FeatureFlag;
use App\Models\FeatureFlagOverride;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FeatureFlagService;

it('returns false for a non-existent flag', function (): void {
    $service = app(FeatureFlagService::class);

    expect($service->isEnabled('non_existent'))->toBeFalse();
});

it('returns the default value when no overrides exist', function (): void {
    $service = app(FeatureFlagService::class);

    $enabledFlag = FeatureFlag::factory()->create(['key' => 'enabled-flag', 'enabled' => true]);
    $disabledFlag = FeatureFlag::factory()->create(['key' => 'disabled-flag', 'enabled' => false]);

    expect($service->isEnabled('enabled-flag'))->toBeTrue()
        ->and($service->isEnabled('disabled-flag'))->toBeFalse();
});

it('tenant override takes precedence over default', function (): void {
    $service = app(FeatureFlagService::class);
    $tenant = Tenant::factory()->create();

    $flag = FeatureFlag::factory()->create(['key' => 'test-flag', 'enabled' => false]);

    FeatureFlagOverride::factory()->create([
        'feature_flag_id' => $flag->id,
        'tenant_id' => $tenant->id,
        'user_id' => null,
        'enabled' => true,
    ]);

    expect($service->isEnabled('test-flag', $tenant))->toBeTrue()
        ->and($service->isEnabled('test-flag'))->toBeFalse(); // no tenant = default
});

it('user override takes precedence over tenant override', function (): void {
    $service = app(FeatureFlagService::class);
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    $flag = FeatureFlag::factory()->create(['key' => 'precedence-flag', 'enabled' => false]);

    // Tenant override enables it
    FeatureFlagOverride::factory()->create([
        'feature_flag_id' => $flag->id,
        'tenant_id' => $tenant->id,
        'user_id' => null,
        'enabled' => true,
    ]);

    // User override disables it
    FeatureFlagOverride::factory()->create([
        'feature_flag_id' => $flag->id,
        'tenant_id' => null,
        'user_id' => $user->id,
        'enabled' => false,
    ]);

    expect($service->isEnabled('precedence-flag', $tenant, $user))->toBeFalse();
});

it('returns default when overrides do not match the given tenant or user', function (): void {
    $service = app(FeatureFlagService::class);
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();

    $flag = FeatureFlag::factory()->create(['key' => 'scoped-flag', 'enabled' => false]);

    FeatureFlagOverride::factory()->create([
        'feature_flag_id' => $flag->id,
        'tenant_id' => $otherTenant->id,
        'user_id' => null,
        'enabled' => true,
    ]);

    expect($service->isEnabled('scoped-flag', $tenant))->toBeFalse();
});
