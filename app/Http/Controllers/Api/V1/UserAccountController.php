<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ChangeEmailRequest;
use App\Http\Requests\Api\V1\DeleteAccountRequest;
use App\Services\UserAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAccountController extends Controller
{
    public function __construct(protected UserAccountService $accountService) {}

    /**
     * Export user data.
     */
    public function export(Request $request): JsonResponse
    {
        $data = $this->accountService->exportData($request->user());

        return response()->json(['data' => $data]);
    }

    /**
     * Delete user account.
     */
    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $result = $this->accountService->deleteAccount($request->user());

        if (! $result['success']) {
            return response()->json([
                'error' => $result['error'],
                'tenant_id' => $result['tenant_id'] ?? null,
                'tenant_name' => $result['tenant_name'] ?? null,
            ], 409);
        }

        return response()->json(['message' => 'Account deleted.']);
    }

    /**
     * Change user email — triggers re-verification.
     */
    public function changeEmail(ChangeEmailRequest $request): JsonResponse
    {
        $this->accountService->changeEmail(
            $request->user(),
            $request->validated('email')
        );

        return response()->json(['message' => 'Email changed. Please verify your new email address.']);
    }
}
