<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RefreshTokenRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    /**
     * Login and get token pair.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $tokens = $this->authService->login(
            email: $request->validated('email'),
            password: $request->validated('password'),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (! $tokens) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        return response()->json($tokens);
    }

    /**
     * Refresh token pair.
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $tokens = $this->authService->refresh(
            refreshTokenPlain: $request->validated('refresh_token'),
        );

        if (! $tokens) {
            return response()->json([
                'message' => 'Invalid or expired refresh token.',
            ], 401);
        }

        return response()->json($tokens);
    }

    /**
     * Logout — revoke current session.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout(
            user: $request->user(),
            refreshTokenPlain: $request->input('refresh_token'),
        );

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user(),
        ]);
    }

    /**
     * Change password — revokes all tokens and sessions.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->validated('password')]);

        $this->authService->revokeAllForUser($user, \App\Enums\RevocationReason::PasswordReset);

        return response()->json(['message' => 'Password changed. All sessions revoked.']);
    }
}
