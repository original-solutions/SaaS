<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscribed
{
    /**
     * Billing endpoint prefixes that are exempt from read-only restrictions.
     *
     * @var array<string>
     */
    protected array $exemptPrefixes = [
        'api/v1/billing',
        'api/v1/account/export',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app(TenantContext::class)->get();

        if (! $tenant) {
            return $next($request);
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->latest()
            ->first();

        if (! $subscription) {
            // No subscription — allow full access (free tier / not yet billing)
            return $next($request);
        }

        if ($subscription->isActive() || $subscription->isInGracePeriod()) {
            return $next($request);
        }

        return $this->handleReadOnly($request, $next);
    }

    protected function handleReadOnly(Request $request, Closure $next): Response
    {
        // GET requests always allowed
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Exempt billing endpoints
        foreach ($this->exemptPrefixes as $prefix) {
            if (str_starts_with($request->path(), $prefix)) {
                return $next($request);
            }
        }

        return response()->json([
            'error' => 'SUBSCRIPTION_READ_ONLY',
            'message' => 'Your subscription does not allow this action.',
        ], 403);
    }
}
