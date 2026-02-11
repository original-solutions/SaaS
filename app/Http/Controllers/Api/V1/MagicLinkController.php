<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RequestMagicLinkRequest;
use App\Http\Requests\Api\V1\VerifyMagicLinkRequest;
use App\Services\AuthService;
use App\Services\MagicLinkService;
use Illuminate\Http\JsonResponse;

class MagicLinkController extends Controller
{
    public function __construct(
        protected MagicLinkService $magicLinkService,
        protected AuthService $authService,
    ) {}

    /**
     * Request a magic login link.
     */
    public function request(RequestMagicLinkRequest $request): JsonResponse
    {
        if (! config('saas.magic_link_login', false)) {
            return response()->json(['message' => 'Magic link login is disabled.'], 403);
        }

        $this->magicLinkService->generate($request->validated('email'));

        // Always return success to avoid email enumeration
        return response()->json(['message' => 'If the email exists, a magic link has been sent.']);
    }

    /**
     * Verify a magic login token and authenticate.
     */
    public function verify(VerifyMagicLinkRequest $request): JsonResponse
    {
        if (! config('saas.magic_link_login', false)) {
            return response()->json(['message' => 'Magic link login is disabled.'], 403);
        }

        $user = $this->magicLinkService->verify($request->validated('token'));

        if (! $user) {
            return response()->json(['message' => 'Invalid or expired magic link.'], 401);
        }

        if ($user->isLocked()) {
            return response()->json(['message' => 'Account is locked.'], 403);
        }

        // Create device session and token pair via AuthService
        $tokens = $this->authService->loginViaUser($user, $request->ip(), $request->userAgent());

        return response()->json($tokens);
    }
}
