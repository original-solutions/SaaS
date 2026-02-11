<?php

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;

it('has isActive and isDisabled methods', function (): void {
    $active = Tenant::factory()->create(['status' => TenantStatus::Active]);
    $disabled = Tenant::factory()->disabled()->create();

    expect($active->isActive())->toBeTrue();
    expect($active->isDisabled())->toBeFalse();
    expect($disabled->isActive())->toBeFalse();
    expect($disabled->isDisabled())->toBeTrue();
});

it('has users relationship', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenant->users()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    expect($tenant->users)->toHaveCount(1);
    expect($tenant->users->first()->id)->toBe($user->id);
});

it('supports soft deletes', function (): void {
    $tenant = Tenant::factory()->create();
    $tenant->delete();

    expect(Tenant::find($tenant->id))->toBeNull();
    expect(Tenant::withTrashed()->find($tenant->id))->not->toBeNull();
});

it('casts status to TenantStatus enum', function (): void {
    $tenant = Tenant::factory()->create();

    expect($tenant->status)->toBeInstanceOf(TenantStatus::class);
});
