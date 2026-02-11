<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Rules\SafeHtml;
use App\Support\RichTextSanitiser;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * List notes for a given noteable.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noteable_type' => ['required', 'string'],
            'noteable_id' => ['required', 'integer'],
        ]);

        $notes = Note::where('noteable_type', $validated['noteable_type'])
            ->where('noteable_id', $validated['noteable_id'])
            ->with('author')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($notes);
    }

    /**
     * Create a note.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', new SafeHtml],
            'noteable_type' => ['required', 'string'],
            'noteable_id' => ['required', 'integer'],
        ]);

        $note = Note::create([
            'tenant_id' => app(TenantContext::class)->id(),
            'author_user_id' => $request->user()->id,
            'body' => RichTextSanitiser::sanitise($validated['body']),
            'noteable_type' => $validated['noteable_type'],
            'noteable_id' => $validated['noteable_id'],
        ]);

        return response()->json(['data' => $note->load('author')], 201);
    }

    /**
     * Update a note.
     */
    public function update(Request $request, Note $note): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', new SafeHtml],
        ]);

        $note->update([
            'body' => RichTextSanitiser::sanitise($validated['body']),
        ]);

        return response()->json(['data' => $note->fresh()->load('author')]);
    }

    /**
     * Delete a note.
     */
    public function destroy(Note $note): JsonResponse
    {
        $note->delete();

        return response()->json(null, 204);
    }
}
