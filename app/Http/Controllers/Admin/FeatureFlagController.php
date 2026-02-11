<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Models\FeatureFlagOverride;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeatureFlagController extends Controller
{
    /**
     * List all feature flags.
     */
    public function index(): JsonResponse
    {
        $flags = FeatureFlag::with('overrides')->get();

        return response()->json(['data' => $flags]);
    }

    /**
     * Create a feature flag.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'unique:feature_flags,key'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'enabled' => ['required', 'boolean'],
        ]);

        $flag = FeatureFlag::create($validated);

        return response()->json(['data' => $flag], 201);
    }

    /**
     * Update a feature flag.
     */
    public function update(Request $request, FeatureFlag $featureFlag): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'enabled' => ['sometimes', 'boolean'],
        ]);

        $featureFlag->update($validated);

        return response()->json(['data' => $featureFlag->fresh()]);
    }

    /**
     * Delete a feature flag.
     */
    public function destroy(FeatureFlag $featureFlag): JsonResponse
    {
        $featureFlag->delete();

        return response()->json(null, 204);
    }

    /**
     * Create or update an override for a feature flag.
     */
    public function storeOverride(Request $request, FeatureFlag $featureFlag): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'enabled' => ['required', 'boolean'],
        ]);

        $override = FeatureFlagOverride::updateOrCreate(
            [
                'feature_flag_id' => $featureFlag->id,
                'tenant_id' => $validated['tenant_id'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
            ],
            [
                'enabled' => $validated['enabled'],
            ]
        );

        return response()->json(['data' => $override], 201);
    }

    /**
     * Delete an override.
     */
    public function destroyOverride(FeatureFlag $featureFlag, FeatureFlagOverride $override): JsonResponse
    {
        $override->delete();

        return response()->json(null, 204);
    }
}
