<?php

use App\Models\Tenant;
use App\Models\User;

it('searches across tenants and users', function (): void {
    Tenant::factory()->create(['name' => 'Acme Corp']);
    User::factory()->create(['name' => 'John Doe']);
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/search?q=Acme')
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['tenants', 'users', 'support_cases']]);
});

it('requires at least 2 characters', function (): void {
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/search?q=a')
        ->assertUnprocessable();
});

it('returns empty results for unknown search', function (): void {
    actingAsSuperAdmin();

    $response = $this->getJson('/admin/api/v1/search?q=zzz_nonexistent')
        ->assertSuccessful();

    expect($response->json('data.tenants'))->toBeEmpty()
        ->and($response->json('data.users'))->toBeEmpty()
        ->and($response->json('data.support_cases'))->toBeEmpty();
});
