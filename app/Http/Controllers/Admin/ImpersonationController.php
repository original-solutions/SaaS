<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Impersonation\ImpersonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function __construct(protected ImpersonationService $impersonationService) {}

    public function start(Request $request, User $user): JsonResponse
    {
        $result = $this->impersonationService->start(
            $request->user(),
            $user,
            $request->input('reason')
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], 403);
        }

        return response()->json($result);
    }

    public function stop(Request $request, User $user): JsonResponse
    {
        $this->impersonationService->stop($request->user(), $user);

        return response()->json(['message' => 'Impersonation stopped.']);
    }
}
