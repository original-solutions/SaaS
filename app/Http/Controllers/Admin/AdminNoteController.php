<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AdminNote::with('createdBy');

        if ($request->has('target_type') && $request->has('target_id')) {
            $query->where('target_type', $request->input('target_type'))
                ->where('target_id', $request->input('target_id'));
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_type' => ['required', 'string', 'in:user,tenant'],
            'target_id' => ['required', 'integer'],
            'note' => ['required', 'string'],
        ]);

        $note = AdminNote::create([
            ...$validated,
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $note->load('createdBy')], 201);
    }

    public function update(Request $request, AdminNote $adminNote): JsonResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string'],
        ]);

        $adminNote->update($validated);

        return response()->json(['data' => $adminNote->fresh()->load('createdBy')]);
    }

    public function destroy(AdminNote $adminNote): JsonResponse
    {
        $adminNote->delete();

        return response()->json(null, 204);
    }
}
