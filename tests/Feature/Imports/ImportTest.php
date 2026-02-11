<?php

use App\Enums\ImportStatus;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create();
    $this->tenant->users()->attach($this->user, ['role' => 'owner', 'joined_at' => now()]);

    app(TenantContext::class)->set($this->tenant);
});

it('creates an import and dispatches a job', function (): void {
    Queue::fake();

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->postJson('/api/v1/imports', [
            'type' => 'contacts',
            'total_rows' => 100,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'contacts')
        ->assertJsonPath('data.status', ImportStatus::Queued->value);

    Queue::assertPushed(ProcessImportJob::class);
});

it('gets import status', function (): void {
    $import = Import::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by_user_id' => $this->user->id,
        'status' => ImportStatus::Running,
        'progress' => 50,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->getJson("/api/v1/imports/{$import->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.progress', 50);
});

it('tracks completed status', function (): void {
    $import = Import::factory()->completed()->create([
        'tenant_id' => $this->tenant->id,
        'created_by_user_id' => $this->user->id,
    ]);

    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->progress)->toBe(100);
});

it('tracks failed status', function (): void {
    $import = Import::factory()->failed()->create([
        'tenant_id' => $this->tenant->id,
        'created_by_user_id' => $this->user->id,
    ]);

    expect($import->status)->toBe(ImportStatus::Failed);
});
