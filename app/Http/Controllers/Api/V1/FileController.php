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
}
