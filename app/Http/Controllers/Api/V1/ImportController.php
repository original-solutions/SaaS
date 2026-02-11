<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(protected ImportService $importService) {}

    /**
     * Create a new import.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string'],
            'input_file_id' => ['nullable', 'integer', 'exists:files,id'],
            'total_rows' => ['nullable', 'integer', 'min:0'],
            'meta' => ['nullable', 'array'],
        ]);

        $import = $this->importService->create(
            $validated['type'],
            $validated['total_rows'] ?? 0,
            $validated['input_file_id'] ?? null,
            $validated['meta'] ?? []
        );

        ProcessImportJob::dispatch($import);

        return response()->json(['data' => $import], 201);
    }

    /**
     * Get import status.
     */
    public function show(Import $import): JsonResponse
    {
        return response()->json(['data' => $import]);
    }
}
