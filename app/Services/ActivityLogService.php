<?php

namespace App\Services;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /**
     * Log an activity using Spatie's activity() helper.
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(string $event, ?Model $subject = null, array $properties = []): void
    {
        $logger = activity()
            ->event($event)
            ->withProperties($properties);

        if ($subject) {
            $logger->performedOn($subject);
        }

        $causer = auth()->user();
        if ($causer) {
            $logger->causedBy($causer);
        }

        // Tenant context
        $tenantContext = app(TenantContext::class);
        if ($tenantContext->has()) {
            $logger->withProperty('tenant_id', $tenantContext->id());
        }

        $logger->log($event);
    }
}
