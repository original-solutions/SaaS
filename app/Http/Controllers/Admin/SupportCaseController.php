<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportCaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SupportCase::with(['tenant', 'user', 'createdBy']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:open,pending,resolved'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'meta' => ['nullable', 'array'],
        ]);

        $case = SupportCase::create([
            ...$validated,
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $case], 201);
    }

    public function show(SupportCase $supportCase): JsonResponse
    {
        return response()->json([
            'data' => $supportCase->load(['tenant', 'user', 'createdBy']),
        ]);
    }

    public function update(Request $request, SupportCase $supportCase): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:open,pending,resolved'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'meta' => ['nullable', 'array'],
        ]);

        $supportCase->update($validated);

        return response()->json(['data' => $supportCase->fresh()]);
    }

    public function destroy(SupportCase $supportCase): JsonResponse
    {
        $supportCase->delete();

        return response()->json(null, 204);
    }
}
