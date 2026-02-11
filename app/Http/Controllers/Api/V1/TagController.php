<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * List all tags for the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tags = Tag::latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json($tags);
    }

    /**
     * Create a tag.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        $tag = Tag::create([
            'tenant_id' => app(TenantContext::class)->id(),
            'name' => $validated['name'],
            'color' => $validated['color'] ?? null,
        ]);

        return response()->json(['data' => $tag], 201);
    }

    /**
     * Update a tag.
     */
    public function update(Request $request, Tag $tag): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        $tag->update($validated);

        return response()->json(['data' => $tag->fresh()]);
    }

    /**
     * Delete a tag.
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(null, 204);
    }

    /**
     * Attach a tag to a taggable model.
     */
    public function attach(Request $request, Tag $tag): JsonResponse
    {
        $validated = $request->validate([
            'taggable_type' => ['required', 'string'],
            'taggable_id' => ['required', 'integer'],
        ]);

        $tag->morphedByMany($validated['taggable_type'], 'taggable')
            ->attach($validated['taggable_id'], [
                'tenant_id' => app(TenantContext::class)->id(),
            ]);

        return response()->json(['message' => 'Tag attached.']);
    }

    /**
     * Detach a tag from a taggable model.
     */
    public function detach(Request $request, Tag $tag): JsonResponse
    {
        $validated = $request->validate([
            'taggable_type' => ['required', 'string'],
            'taggable_id' => ['required', 'integer'],
        ]);

        $tag->morphedByMany($validated['taggable_type'], 'taggable')
            ->detach($validated['taggable_id']);

        return response()->json(['message' => 'Tag detached.']);
    }
}
