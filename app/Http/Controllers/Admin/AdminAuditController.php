<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AdminAuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Activity::query();

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->input('log_name'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $activities = $query->latest()->paginate($perPage);

        return response()->json($activities);
    }
}
