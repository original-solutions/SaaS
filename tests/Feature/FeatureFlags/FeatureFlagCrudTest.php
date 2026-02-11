<?php

use App\Models\FeatureFlag;
use App\Models\FeatureFlagOverride;
use App\Models\Tenant;

it('lists all feature flags (super admin)', function (): void {
    FeatureFlag::factory()->count(3)->create();

    $response = actingAsSuperAdmin()
        ->getJson('/admin/api/v1/feature-flags');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('creates a feature flag', function (): void {
    $response = actingAsSuperAdmin()
        ->postJson('/admin/api/v1/feature-flags', [
            'key' => 'new-feature',
            'name' => 'New Feature',
            'description' => 'A brand new feature',
            'enabled' => true,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.key', 'new-feature');

    $this->assertDatabaseHas('feature_flags', ['key' => 'new-feature']);
});

it('updates a feature flag', function (): void {
    $flag = FeatureFlag::factory()->create(['enabled' => false]);

    $response = actingAsSuperAdmin()
        ->putJson("/admin/api/v1/feature-flags/{$flag->id}", [
            'enabled' => true,
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.enabled', true);

    expect($flag->fresh()->enabled)->toBeTrue();
});

it('deletes a feature flag', function (): void {
    $flag = FeatureFlag::factory()->create();

    $response = actingAsSuperAdmin()
        ->deleteJson("/admin/api/v1/feature-flags/{$flag->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('feature_flags', ['id' => $flag->id]);
});

it('creates an override for a feature flag', function (): void {
    $flag = FeatureFlag::factory()->create();
    $tenant = Tenant::factory()->create();

    $response = actingAsSuperAdmin()
        ->postJson("/admin/api/v1/feature-flags/{$flag->id}/overrides", [
            'tenant_id' => $tenant->id,
            'enabled' => true,
        ]);

    $response->assertCreated();

    $this->assertDatabaseHas('feature_flag_overrides', [
        'feature_flag_id' => $flag->id,
        'tenant_id' => $tenant->id,
        'enabled' => true,
    ]);
});

it('deletes an override', function (): void {
    $flag = FeatureFlag::factory()->create();
    $override = FeatureFlagOverride::factory()->create([
        'feature_flag_id' => $flag->id,
    ]);

    $response = actingAsSuperAdmin()
        ->deleteJson("/admin/api/v1/feature-flags/{$flag->id}/overrides/{$override->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('feature_flag_overrides', ['id' => $override->id]);
});
