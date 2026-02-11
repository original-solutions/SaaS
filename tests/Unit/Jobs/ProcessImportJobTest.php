<?php

use App\Enums\ImportStatus;
use App\Jobs\ProcessImportJob;
use App\Models\File;
use App\Models\Import;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create();

    app(TenantContext::class)->set($this->tenant);
});

it('processes a CSV file and updates progress', function (): void {
    $csvContent = "name,email\nJohn,john@example.com\nJane,jane@example.com\n";
    Storage::disk('local')->put('test/import.csv', $csvContent);

    $file = File::factory()->create([
        'tenant_id' => $this->tenant->id,
        'uploaded_by_user_id' => $this->user->id,
        'path' => 'test/import.csv',
        'disk' => 'local',
    ]);

    $import = Import::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by_user_id' => $this->user->id,
        'input_file_id' => $file->id,
        'total_rows' => 0,
    ]);

    $job = new ProcessImportJob($import);
    $job->handle(app(\App\Services\ImportService::class));

    $import->refresh();

    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->progress)->toBe(100)
        ->and($import->processed_rows)->toBe(2)
        ->and($import->error_count)->toBe(0);
});

it('fails gracefully when no input file is provided', function (): void {
    $import = Import::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by_user_id' => $this->user->id,
        'input_file_id' => null,
    ]);

    $job = new ProcessImportJob($import);
    $job->handle(app(\App\Services\ImportService::class));

    $import->refresh();

    expect($import->status)->toBe(ImportStatus::Failed);
});
