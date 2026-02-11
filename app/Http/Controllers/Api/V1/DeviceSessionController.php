<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RevocationReason;
use App\Http\Controllers\Controller;
use App\Models\DeviceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceSessionController extends Controller
{
    /**
     * List the authenticated user's device sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $sessions = $request->user()
            ->deviceSessions()
            ->active()
            ->latest('last_active_at')
            ->get();

        return response()->json(['data' => $sessions]);
    }

    /**
     * Revoke a specific device session.
     */
    public function destroy(Request $request, DeviceSession $session): JsonResponse
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }

        $session->revoke(RevocationReason::Logout);

        return response()->json(['message' => 'Session revoked.']);
    }

    /**
     * Revoke all device sessions except the current one.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()?->id;

        $request->user()
            ->deviceSessions()
            ->active()
            ->each(fn (DeviceSession $session) => $session->revoke(RevocationReason::Logout));

        return response()->json(['message' => 'All other sessions revoked.']);
    }
}
