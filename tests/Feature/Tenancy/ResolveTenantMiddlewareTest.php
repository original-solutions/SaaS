<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MembershipService;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->user = User::factory()->create();
    $this->membershipService = app(MembershipService::class);
});

it('returns 400 TENANT_REQUIRED when header missing on tenant-scoped routes', function (): void {
    // Tenant-scoped routes use 'tenant' middleware — test a dummy route
    // For now, test via the middleware directly by calling a tenant-scoped endpoint
    // We'll register a test route for this
    \Illuminate\Support\Facades\Route::middleware(['api', 'auth:sanctum', 'tenant'])
        ->prefix('api/v1/test')
        ->group(function (): void {
            \Illuminate\Support\Facades\Route::get('/tenant-check', fn () => response()->json(['ok' => true]));
        });

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/test/tenant-check');

    $response->assertStatus(400)
        ->assertJson(['error' => 'TENANT_REQUIRED']);
});

it('allows auth and me endpoints without tenant header', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/me');

    $response->assertSuccessful();
});

it('allows tenants list without tenant header', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/tenants');

    $response->assertSuccessful();
});

it('returns 404 for invalid tenant ID', function (): void {
    \Illuminate\Support\Facades\Route::middleware(['api', 'auth:sanctum', 'tenant'])
        ->prefix('api/v1/test')
        ->group(function (): void {
            \Illuminate\Support\Facades\Route::get('/tenant-check', fn () => response()->json(['ok' => true]));
        });

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/test/tenant-check', ['X-Tenant-ID' => '99999']);

    $response->assertNotFound();
});

it('returns 403 when user is not a member', function (): void {
    $tenant = Tenant::factory()->create();

    \Illuminate\Support\Facades\Route::middleware(['api', 'auth:sanctum', 'tenant'])
        ->prefix('api/v1/test')
        ->group(function (): void {
            \Illuminate\Support\Facades\Route::get('/tenant-check', fn () => response()->json(['ok' => true]));
        });

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/test/tenant-check', ['X-Tenant-ID' => (string) $tenant->id]);

    $response->assertForbidden();
});

it('rejects disabled tenant for non-super-admin', function (): void {
    $tenant = Tenant::factory()->disabled()->create();
    $this->membershipService->addMember($tenant, $this->user, TenantRole::Member);

    \Illuminate\Support\Facades\Route::middleware(['api', 'auth:sanctum', 'tenant'])
        ->prefix('api/v1/test')
        ->group(function (): void {
            \Illuminate\Support\Facades\Route::get('/tenant-check', fn () => response()->json(['ok' => true]));
        });

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/test/tenant-check', ['X-Tenant-ID' => (string) $tenant->id]);

    $response->assertForbidden();
});

it('allows disabled tenant for super admin', function (): void {
    $superAdmin = User::factory()->superAdmin()->create();
    $tenant = Tenant::factory()->disabled()->create();

    \Illuminate\Support\Facades\Route::middleware(['api', 'auth:sanctum', 'tenant'])
        ->prefix('api/v1/test')
        ->group(function (): void {
            \Illuminate\Support\Facades\Route::get('/tenant-check', fn () => response()->json(['ok' => true]));
        });

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/v1/test/tenant-check', ['X-Tenant-ID' => (string) $tenant->id]);

    $response->assertSuccessful();
});

it('resolves active tenant and sets context', function (): void {
    $tenant = Tenant::factory()->create();
    $this->membershipService->addMember($tenant, $this->user, TenantRole::Member);

    \Illuminate\Support\Facades\Route::middleware(['api', 'auth:sanctum', 'tenant'])
        ->prefix('api/v1/test')
        ->group(function (): void {
            \Illuminate\Support\Facades\Route::get('/tenant-check', function () {
                $ctx = app(\App\Support\Tenancy\TenantContext::class);

                return response()->json([
                    'has_tenant' => $ctx->has(),
                    'tenant_id' => $ctx->id(),
                ]);
            });
        });

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/test/tenant-check', ['X-Tenant-ID' => (string) $tenant->id]);

    $response->assertSuccessful()
        ->assertJson([
            'has_tenant' => true,
            'tenant_id' => $tenant->id,
        ]);
});
