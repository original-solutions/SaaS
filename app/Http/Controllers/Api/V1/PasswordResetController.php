<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    /**
     * Send a password reset link to the user's email.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(
            $request->only('email'),
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 422);
    }

    /**
     * Reset the user's password.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all tokens and sessions on password reset
                $this->authService->revokeAllForUser($user, \App\Enums\RevocationReason::PasswordReset);

                event(new PasswordReset($user));
            },
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 422);
    }
}
