<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        protected TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (! $tenantId) {
            return response()->json(['error' => 'TENANT_REQUIRED'], 400);
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json(['error' => 'TENANT_NOT_FOUND'], 404);
        }

        $user = $request->user();

        // Disabled tenant — only Super Admin can access
        if ($tenant->isDisabled() && (! $user || ! $user->isSuperAdmin())) {
            return response()->json(['error' => 'TENANT_DISABLED'], 403);
        }

        // Check user is a member of this tenant (Super Admin bypasses)
        if ($user && ! $user->isSuperAdmin()) {
            $isMember = $user->tenants()->where('tenants.id', $tenant->id)->exists();

            if (! $isMember) {
                return response()->json(['error' => 'TENANT_ACCESS_DENIED'], 403);
            }
        }

        // Set tenant in context
        $this->tenantContext->set($tenant);

        // Set Spatie permission team context
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        // Unset cached roles/permissions on user so Spatie re-resolves with team scope
        if ($user) {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }

        return $next($request);
    }
}
