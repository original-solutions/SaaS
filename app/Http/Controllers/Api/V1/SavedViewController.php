<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SavedView;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedViewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $views = SavedView::where('user_id', $request->user()->id)
            ->when($request->has('resource_type'), fn ($q) => $q->where('resource_type', $request->input('resource_type')))
            ->latest()
            ->get();

        return response()->json(['data' => $views]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'config' => ['required', 'array'],
        ]);

        $view = SavedView::create([
            'tenant_id' => app(TenantContext::class)->id(),
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        return response()->json(['data' => $view], 201);
    }

    public function update(Request $request, SavedView $savedView): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'config' => ['sometimes', 'array'],
        ]);

        $savedView->update($validated);

        return response()->json(['data' => $savedView->fresh()]);
    }

    public function destroy(SavedView $savedView): JsonResponse
    {
        $savedView->delete();

        return response()->json(null, 204);
    }

    public function setDefault(Request $request, SavedView $savedView): JsonResponse
    {
        // Unset any existing default for the same resource type
        SavedView::where('user_id', $request->user()->id)
            ->where('resource_type', $savedView->resource_type)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $savedView->update(['is_default' => true]);

        return response()->json(['data' => $savedView->fresh()]);
    }
}
