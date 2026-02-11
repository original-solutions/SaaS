<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $recentActions = Activity::with('causer')
            ->whereHasMorph('causer', [\App\Models\User::class], function ($query): void {
                $query->where('is_super_admin', true);
            })
            ->latest()
            ->limit(20)
            ->get();

        return response()->json(['data' => $recentActions]);
    }
}
