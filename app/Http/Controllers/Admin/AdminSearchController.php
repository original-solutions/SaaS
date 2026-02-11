<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSearchController extends Controller
{
    public function __construct(protected AdminSearchService $searchService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['error' => 'Query must be at least 2 characters.'], 422);
        }

        return response()->json(['data' => $this->searchService->search($query)]);
    }
}
