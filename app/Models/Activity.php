<?php

namespace App\Models;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    /**
     * PII fields to redact from properties JSON.
     *
     * @var array<int, string>
     */
    protected static array $piiDenylist = [
        'password',
        'password_confirmation',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'token',
        'refresh_token',
        'credit_card',
        'ssn',
    ];

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

            // Redact PII from properties before storing
            if ($activity->properties) {
                $activity->properties = static::redactProperties($activity->properties);
            }
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

    /**
     * Scope to get only redacted activities (strips PII from results).
     */
    public function scopeRedacted(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Redact PII fields from properties collection.
     *
     * @param  \Illuminate\Support\Collection  $properties
     */
    protected static function redactProperties($properties): \Illuminate\Support\Collection
    {
        $data = $properties->toArray();

        array_walk_recursive($data, function (&$value, $key): void {
            if (in_array(strtolower($key), static::$piiDenylist, true)) {
                $value = '[REDACTED]';
            }
        });

        return collect($data);
    }
}
