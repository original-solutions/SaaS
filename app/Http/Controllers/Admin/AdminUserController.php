<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(protected AdminUserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('locked')) {
            $query->whereNotNull('locked_at');
        }

        return response()->json($query->paginate($request->integer('per_page', 15)));
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->load(['tenants', 'deviceSessions', 'loginEvents']),
        ]);
    }

    public function lock(User $user): JsonResponse
    {
        $this->userService->lock($user);

        return response()->json(['message' => 'User locked.']);
    }

    public function unlock(User $user): JsonResponse
    {
        $this->userService->unlock($user);

        return response()->json(['message' => 'User unlocked.']);
    }

    public function resetPassword(User $user): JsonResponse
    {
        $newPassword = $this->userService->resetPassword($user);

        return response()->json(['password' => $newPassword]);
    }

    public function revokeSessions(User $user): JsonResponse
    {
        $this->userService->revokeSessions($user);

        return response()->json(['message' => 'All sessions revoked.']);
    }
}
