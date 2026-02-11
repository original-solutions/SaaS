<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfirmTwoFactorRequest;
use App\Http\Requests\Api\V1\DisableTwoFactorRequest;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function __construct(protected TwoFactorService $twoFactorService) {}

    /**
     * Enable 2FA — generate secret and QR code URI.
     */
    public function enable(Request $request): JsonResponse
    {
        if (! config('saas.two_factor_enabled', true)) {
            return response()->json(['message' => 'Two-factor authentication is disabled.'], 403);
        }

        if ($request->user()->hasTwoFactorEnabled()) {
            return response()->json(['message' => 'Two-factor authentication is already enabled.'], 422);
        }

        $result = $this->twoFactorService->enable($request->user());

        return response()->json([
            'secret' => $result['secret'],
            'qr_uri' => $result['qr_uri'],
            'recovery_codes' => json_decode(decrypt($request->user()->fresh()->two_factor_recovery_codes), true),
        ]);
    }

    /**
     * Confirm 2FA activation with a TOTP code.
     */
    public function confirm(ConfirmTwoFactorRequest $request): JsonResponse
    {
        $valid = $this->twoFactorService->confirm($request->user(), $request->validated('code'));

        if (! $valid) {
            return response()->json(['message' => 'Invalid two-factor code.'], 422);
        }

        return response()->json(['message' => 'Two-factor authentication confirmed.']);
    }

    /**
     * Disable 2FA — requires password confirmation.
     */
    public function disable(DisableTwoFactorRequest $request): JsonResponse
    {
        $this->twoFactorService->disable($request->user());

        return response()->json(['message' => 'Two-factor authentication disabled.']);
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        if (! $request->user()->hasTwoFactorEnabled()) {
            return response()->json(['message' => 'Two-factor authentication is not enabled.'], 422);
        }

        $codes = $this->twoFactorService->regenerateRecoveryCodes($request->user());

        return response()->json(['recovery_codes' => $codes]);
    }
}
