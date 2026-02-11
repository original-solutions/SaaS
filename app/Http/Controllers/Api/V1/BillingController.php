<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;

class BillingController extends Controller
{
    public function index(): JsonResponse
    {
        $tenant = app(TenantContext::class)->get();

        if (! $tenant) {
            return response()->json(['error' => 'Tenant context required.'], 400);
        }

        $subscription = Subscription::with('plan')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->first();

        return response()->json([
            'data' => $subscription ? [
                'plan' => $subscription->plan,
                'status' => $subscription->status,
                'trial_ends_at' => $subscription->trial_ends_at,
                'current_period_end' => $subscription->current_period_end,
                'grace_period_ends_at' => $subscription->grace_period_ends_at,
                'is_read_only' => $subscription->isReadOnly(),
            ] : null,
        ]);
    }

    public function plans(): JsonResponse
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $plans]);
    }
}
