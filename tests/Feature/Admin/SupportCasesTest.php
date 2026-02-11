<?php

use App\Enums\SupportCasePriority;
use App\Models\SupportCase;
use App\Models\Tenant;

it('lists support cases', function (): void {
    SupportCase::factory()->count(3)->create();
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/support-cases')
        ->assertSuccessful()
        ->assertJsonPath('total', 3);
});

it('filters support cases by status', function (): void {
    SupportCase::factory()->open()->create();
    SupportCase::factory()->resolved()->create();
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/support-cases?status=open')
        ->assertSuccessful()
        ->assertJsonPath('total', 1);
});

it('filters support cases by priority', function (): void {
    SupportCase::factory()->highPriority()->create();
    SupportCase::factory()->create(['priority' => SupportCasePriority::Low]);
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/support-cases?priority=high')
        ->assertSuccessful()
        ->assertJsonPath('total', 1);
});

it('creates a support case', function (): void {
    $tenant = Tenant::factory()->create();
    actingAsSuperAdmin();

    $this->postJson('/admin/api/v1/support-cases', [
        'tenant_id' => $tenant->id,
        'subject' => 'Cannot access dashboard',
        'priority' => 'high',
    ])->assertCreated()
        ->assertJsonPath('data.subject', 'Cannot access dashboard');
});

it('shows a support case', function (): void {
    $case = SupportCase::factory()->create();
    actingAsSuperAdmin();

    $this->getJson("/admin/api/v1/support-cases/{$case->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $case->id);
});

it('updates a support case', function (): void {
    $case = SupportCase::factory()->open()->create();
    actingAsSuperAdmin();

    $this->putJson("/admin/api/v1/support-cases/{$case->id}", [
        'status' => 'resolved',
    ])->assertSuccessful()
        ->assertJsonPath('data.status', 'resolved');
});

it('deletes a support case', function (): void {
    $case = SupportCase::factory()->create();
    actingAsSuperAdmin();

    $this->deleteJson("/admin/api/v1/support-cases/{$case->id}")
        ->assertNoContent();

    expect(SupportCase::find($case->id))->toBeNull();
});
