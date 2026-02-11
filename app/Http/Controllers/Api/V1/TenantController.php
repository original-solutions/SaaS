<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TenantRole;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateTenantRequest;
use App\Http\Requests\Api\V1\UpdateTenantRequest;
use App\Models\Tenant;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(protected MembershipService $membershipService) {}

    /**
     * List the authenticated user's tenants.
     */
    public function index(Request $request): JsonResponse
    {
        $tenants = $request->user()
            ->tenants()
            ->withPivot('role', 'joined_at')
            ->get();

        return response()->json(['data' => $tenants]);
    }

    /**
     * Create a new tenant — user becomes Owner.
     */
    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = Tenant::create([
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug'),
            'status' => TenantStatus::Active,
        ]);

        $this->membershipService->addMember($tenant, $request->user(), TenantRole::Owner);

        return response()->json([
            'data' => $tenant->fresh()->load('users'),
        ], 201);
    }

    /**
     * Show a specific tenant.
     */
    public function show(Request $request, Tenant $tenant): JsonResponse
    {
        // User must be a member or super admin
        if (! $request->user()->isSuperAdmin()) {
            $isMember = $request->user()->tenants()->where('tenants.id', $tenant->id)->exists();
            if (! $isMember) {
                abort(403);
            }
        }

        return response()->json(['data' => $tenant]);
    }

    /**
     * Update a tenant.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant->update($request->validated());

        return response()->json(['data' => $tenant->fresh()]);
    }

    /**
     * Soft delete a tenant.
     */
    public function destroy(Request $request, Tenant $tenant): JsonResponse
    {
        // Only Owner or Super Admin can delete
        if (! $request->user()->isSuperAdmin()) {
            $isOwner = $request->user()->tenants()
                ->where('tenants.id', $tenant->id)
                ->wherePivot('role', TenantRole::Owner->value)
                ->exists();

            if (! $isOwner) {
                abort(403);
            }
        }

        $tenant->update(['status' => TenantStatus::Deleted]);
        $tenant->delete();

        return response()->json(['message' => 'Tenant deleted.']);
    }
}
