<?php

use App\Models\File;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    Storage::fake('local');

    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create();
    $this->tenant->users()->attach($this->user, ['role' => 'owner', 'joined_at' => now()]);
});

it('uploads a file', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->postJson('/api/v1/files', [
            'file' => UploadedFile::fake()->create('document.pdf', 1024),
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.original_name', 'document.pdf');

    $this->assertDatabaseHas('files', [
        'tenant_id' => $this->tenant->id,
        'original_name' => 'document.pdf',
        'uploaded_by_user_id' => $this->user->id,
    ]);
});

it('downloads a file', function (): void {
    Storage::disk('local')->put('test/file.txt', 'Hello World');

    $file = File::factory()->create([
        'tenant_id' => $this->tenant->id,
        'uploaded_by_user_id' => $this->user->id,
        'path' => 'test/file.txt',
        'disk' => 'local',
        'original_name' => 'file.txt',
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->get("/api/v1/files/{$file->id}/download");

    $response->assertSuccessful();
});

it('deletes a file as uploader', function (): void {
    Storage::disk('local')->put('test/delete.txt', 'content');

    $file = File::factory()->create([
        'tenant_id' => $this->tenant->id,
        'uploaded_by_user_id' => $this->user->id,
        'path' => 'test/delete.txt',
        'disk' => 'local',
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->deleteJson("/api/v1/files/{$file->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('files', ['id' => $file->id]);
});

it('prevents non-uploader non-admin from deleting a file', function (): void {
    Storage::disk('local')->put('test/other.txt', 'content');

    $uploader = User::factory()->create();
    $this->tenant->users()->attach($uploader, ['role' => 'member', 'joined_at' => now()]);

    $otherUser = User::factory()->create();
    $this->tenant->users()->attach($otherUser, ['role' => 'member', 'joined_at' => now()]);

    $file = File::factory()->create([
        'tenant_id' => $this->tenant->id,
        'uploaded_by_user_id' => $uploader->id,
        'path' => 'test/other.txt',
        'disk' => 'local',
    ]);

    $response = $this->actingAs($otherUser, 'sanctum')
        ->withHeader('X-Tenant-ID', $this->tenant->id)
        ->deleteJson("/api/v1/files/{$file->id}");

    $response->assertForbidden();
});

it('tenant isolates files', function (): void {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create();
    $otherTenant->users()->attach($otherUser, ['role' => 'owner', 'joined_at' => now()]);

    File::factory()->create(['tenant_id' => $this->tenant->id, 'uploaded_by_user_id' => $this->user->id]);

    app(\App\Support\Tenancy\TenantContext::class)->clear();
    app(\App\Support\Tenancy\TenantContext::class)->set($otherTenant);
    File::factory()->create(['tenant_id' => $otherTenant->id, 'uploaded_by_user_id' => $otherUser->id]);

    // In otherTenant context, should see 1
    expect(File::count())->toBe(1);

    // Switch back
    app(\App\Support\Tenancy\TenantContext::class)->clear();
    app(\App\Support\Tenancy\TenantContext::class)->set($this->tenant);
    expect(File::count())->toBe(1);
});
