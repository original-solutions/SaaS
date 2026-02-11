<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Activity::query();

        if ($request->filled('subject_type') && $request->filled('subject_id')) {
            $query->where('subject_type', $request->input('subject_type'))
                ->where('subject_id', $request->input('subject_id'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $activities = $query->latest()->paginate($perPage);

        return response()->json($activities);
    }
}
