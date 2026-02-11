<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\AdminTenantService;
use App\Services\TenantUsageStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTenantController extends Controller
{
    public function __construct(
        protected AdminTenantService $tenantService,
        protected TenantUsageStatsService $statsService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Tenant::withTrashed();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate($request->integer('per_page', 15)));
    }

    public function show(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => $tenant,
            'stats' => $this->statsService->getStats($tenant),
        ]);
    }

    public function members(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => $tenant->users()->get(),
        ]);
    }

    public function disable(Tenant $tenant): JsonResponse
    {
        $this->tenantService->disable($tenant);

        return response()->json(['message' => 'Tenant disabled.']);
    }

    public function enable(Tenant $tenant): JsonResponse
    {
        $this->tenantService->enable($tenant);

        return response()->json(['message' => 'Tenant enabled.']);
    }

    public function forceLogout(Tenant $tenant): JsonResponse
    {
        $count = $this->tenantService->forceLogout($tenant);

        return response()->json(['message' => "Force logged out {$count} members."]);
    }

    public function export(Tenant $tenant): JsonResponse
    {
        return response()->json(['data' => $this->tenantService->exportData($tenant)]);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->tenantService->delete($tenant);

        return response()->json(null, 204);
    }
}
