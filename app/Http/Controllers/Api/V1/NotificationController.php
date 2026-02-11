<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List notifications (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate($request->integer('per_page', 15));

        return response()->json($notifications);
    }

    /**
     * Mark a notification as read.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * List notification preferences.
     */
    public function preferences(Request $request): JsonResponse
    {
        $preferences = NotificationPreference::where('user_id', $request->user()->id)
            ->when($request->header('X-Tenant-ID'), function ($query, $tenantId): void {
                $query->where('tenant_id', $tenantId);
            })
            ->get();

        return response()->json(['data' => $preferences]);
    }

    /**
     * Update a notification preference.
     */
    public function updatePreference(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'channels' => ['required', 'array'],
            'channels.*' => ['string', 'in:email,database,broadcast'],
        ]);

        $preference = NotificationPreference::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'tenant_id' => $request->header('X-Tenant-ID'),
                'key' => $validated['key'],
            ],
            [
                'channels' => $validated['channels'],
            ]
        );

        return response()->json(['data' => $preference]);
    }
}
