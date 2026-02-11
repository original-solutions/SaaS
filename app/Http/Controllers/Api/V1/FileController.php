<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function __construct(protected FileService $fileService) {}

    public function index(Request $request): JsonResponse
    {
        $query = File::query();

        if ($request->filled('fileable_type') && $request->filled('fileable_id')) {
            $fileableType = $request->input('fileable_type');
            $fileableId = $request->input('fileable_id');
            // Use the correct morph relationship based on fileable_type
            if ($fileableType === 'tenant' || $fileableType === 'App\\Models\\Tenant') {
                $query->whereHas('tenants', function ($q) use ($fileableId) {
                    $q->where('id', $fileableId);
                });
            }
            // Add more morph types here as needed
        }

        $perPage = (int) $request->input('per_page', 20);
        $files = $query->latest()->paginate($perPage);

        return response()->json($files);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max, adjust as needed
        ]);

        $file = $this->fileService->upload($request->file('file'));

        return response()->json([
            'data' => $file,
        ], 201);
    }

    public function download(File $file)
    {
        // Authorize download (optional: add FilePolicy)
        $url = $this->fileService->download($file);

        // If using local disk, stream the file
        if ($file->disk === 'local') {
            $fullPath = \Storage::disk('local')->path($file->path);

            return response()->download($fullPath, $file->original_name);
        }

        // Otherwise, redirect to presigned URL
        return redirect()->away($url);
    }

    public function destroy(File $file)
    {
        // Only uploader or admin can delete (add FilePolicy for real app)
        if (auth()->id() !== $file->uploaded_by_user_id && ! auth()->user()->hasRole(['owner', 'admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $this->fileService->delete($file);

        return response()->noContent();
    }

    public function uploadUrl(Request $request): JsonResponse
    {
        $request->validate([
            'filename' => 'required|string',
            'mime_type' => 'required|string',
        ]);

        $result = $this->fileService->generateUploadUrl(
            $request->input('filename'),
            $request->input('mime_type')
        );

        return response()->json($result);
    }
}
