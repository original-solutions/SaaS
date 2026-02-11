<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(protected FileService $fileService) {}

    /**
     * Upload a file.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // 50MB max
        ]);

        $file = $this->fileService->upload(
            $request->file('file'),
            $request->input('disk', 'local')
        );

        return response()->json(['data' => $file], 201);
    }

    /**
     * Download a file (streamed or redirect to signed URL).
     */
    public function download(File $file): StreamedResponse|JsonResponse
    {
        $url = $this->fileService->download($file);

        // If it's a path (local), stream it
        if (! str_starts_with($url, 'http')) {
            return Storage::disk($file->disk)->download($file->path, $file->original_name);
        }

        return response()->json(['url' => $url]);
    }

    /**
     * Request a presigned upload URL.
     */
    public function uploadUrl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filename' => ['required', 'string'],
            'mime_type' => ['required', 'string'],
            'disk' => ['sometimes', 'string'],
        ]);

        $result = $this->fileService->generateUploadUrl(
            $validated['filename'],
            $validated['mime_type'],
            $validated['disk'] ?? 'local'
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Delete a file.
     */
    public function destroy(File $file): JsonResponse
    {
        // Only uploader or admin can delete
        $user = auth()->user();
        if ($file->uploaded_by_user_id !== $user->id && ! $user->isSuperAdmin()) {
            setPermissionsTeamId($file->tenant_id);
            $user->unsetRelation('roles');
            if (! $user->hasAnyRole(['owner', 'admin'])) {
                abort(403, 'Only the uploader or admins can delete files.');
            }
        }

        $this->fileService->delete($file);

        return response()->json(null, 204);
    }
}
