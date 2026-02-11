<?php

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait for models that belong to a tenant.
 * Auto-applies tenant scope and auto-sets tenant_id on creating.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            if (! $model->tenant_id) {
                $tenantContext = app(TenantContext::class);
                if ($tenantContext->has()) {
                    $model->tenant_id = $tenantContext->id();
                }
            }
        });
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
