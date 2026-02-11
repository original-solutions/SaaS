<?php

namespace App\Models;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    /**
     * Boot the model and auto-fill custom columns on creating.
     */
    protected static function booted(): void
    {
        static::creating(function (Activity $activity): void {
            $tenantContext = app(TenantContext::class);

            if ($tenantContext->has()) {
                $activity->tenant_id ??= $tenantContext->id();
            }

            $activity->ip_address ??= request()?->ip();
            $activity->user_agent ??= request()?->userAgent();
            $activity->request_id ??= request()?->header('X-Request-ID');
        });
    }

    /**
     * The tenant this activity belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The user who was impersonating when this activity was performed.
     */
    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonator_user_id');
    }
}
