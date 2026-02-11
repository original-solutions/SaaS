<?php

use App\Models\Tenant;
use App\Support\Tenancy\BelongsToTenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

// Use a test-scoped model to verify the BelongsToTenant trait
beforeEach(function (): void {
    // Create a test table for scoping tests
    Schema::create('tenant_scoped_items', function ($table): void {
        $table->id();
        $table->foreignId('tenant_id')->nullable();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('tenant_scoped_items');
    app(TenantContext::class)->clear();
});

// Anonymous class for testing
function createTestModel(): string
{
    return new class extends Model
    {
        use BelongsToTenant;

        protected $table = 'tenant_scoped_items';

        protected $fillable = ['tenant_id', 'name'];
    }::class;
}

it('auto-applies tenant scope on queries', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $modelClass = createTestModel();

    // Create items for both tenants (without scope)
    $modelClass::withoutGlobalScopes()->create(['tenant_id' => $tenantA->id, 'name' => 'Item A']);
    $modelClass::withoutGlobalScopes()->create(['tenant_id' => $tenantB->id, 'name' => 'Item B']);

    // Set tenant context to A
    app(TenantContext::class)->set($tenantA);

    $items = $modelClass::all();
    expect($items)->toHaveCount(1);
    expect($items->first()->name)->toBe('Item A');
});

it('auto-sets tenant_id on creating', function (): void {
    $tenant = Tenant::factory()->create();
    $modelClass = createTestModel();

    app(TenantContext::class)->set($tenant);

    $item = $modelClass::create(['name' => 'Auto Scoped']);

    expect($item->tenant_id)->toBe($tenant->id);
});

it('prevents cross-tenant data access', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $modelClass = createTestModel();

    $modelClass::withoutGlobalScopes()->create(['tenant_id' => $tenantA->id, 'name' => 'Secret A']);
    $modelClass::withoutGlobalScopes()->create(['tenant_id' => $tenantB->id, 'name' => 'Secret B']);

    // Set tenant context to B
    app(TenantContext::class)->set($tenantB);

    $items = $modelClass::all();
    expect($items)->toHaveCount(1);
    expect($items->first()->name)->toBe('Secret B');

    // Cannot find tenant A's item
    $found = $modelClass::where('name', 'Secret A')->first();
    expect($found)->toBeNull();
});
