<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendInvitationRequest;
use App\Http\Requests\Api\V1\UpdateInvitationRoleRequest;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function __construct(protected InvitationService $invitationService) {}

    /**
     * Send an invitation to join a tenant.
     */
    public function store(SendInvitationRequest $request, Tenant $tenant): JsonResponse
    {
        $user = $request->user();

        // Authorization: only Owner/Admin can invite
        setPermissionsTeamId($tenant->id);
        $user->unsetRelation('roles');
        if (! $user->hasAnyRole(['owner', 'admin'])) {
            abort(403, 'Only owners and admins can send invitations.');
        }

        $invitation = $this->invitationService->invite(
            $tenant,
            $request->validated('email'),
            TenantRole::from($request->validated('role')),
            $user
        );

        return response()->json(['data' => $invitation], 201);
    }

    /**
     * View invitation details by token.
     */
    public function show(string $token): JsonResponse
    {
        $invitation = TenantInvitation::where('token', $token)->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $invitation->id,
                'tenant_name' => $invitation->tenant->name,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'expires_at' => $invitation->expires_at,
                'is_expired' => $invitation->isExpired(),
                'is_pending' => $invitation->isPending(),
            ],
        ]);
    }

    /**
     * Accept an invitation.
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = TenantInvitation::where('token', $token)->firstOrFail();
        $result = $this->invitationService->accept($invitation, $request->user());

        if (! $result['success']) {
            $status = match ($result['error']) {
                'INVITE_EMAIL_MISMATCH' => 403,
                'INVITE_EXPIRED' => 410,
                'INVITE_ALREADY_ACCEPTED', 'INVITE_ALREADY_DECLINED' => 409,
                'EMAIL_NOT_VERIFIED' => 403,
                default => 400,
            };

            return response()->json([
                'error' => $result['error'],
                'expected_email' => $result['expected_email'] ?? null,
            ], $status);
        }

        return response()->json(['message' => 'Invitation accepted.']);
    }

    /**
     * Decline an invitation.
     */
    public function decline(Request $request, string $token): JsonResponse
    {
        $invitation = TenantInvitation::where('token', $token)->firstOrFail();

        if (! $this->invitationService->decline($invitation)) {
            return response()->json(['error' => 'Invitation is no longer pending.'], 409);
        }

        return response()->json(['message' => 'Invitation declined.']);
    }

    /**
     * Resend an invitation.
     */
    public function resend(Request $request, Tenant $tenant, TenantInvitation $invitation): JsonResponse
    {
        $user = $request->user();
        setPermissionsTeamId($tenant->id);
        $user->unsetRelation('roles');
        if (! $user->hasAnyRole(['owner', 'admin'])) {
            abort(403);
        }

        if (! $invitation->isPending()) {
            return response()->json(['error' => 'Invitation is no longer pending.'], 409);
        }

        $updated = $this->invitationService->resendInvitation($invitation);

        return response()->json(['data' => $updated]);
    }

    /**
     * Update the role on a pending invitation.
     */
    public function updateRole(UpdateInvitationRoleRequest $request, Tenant $tenant, TenantInvitation $invitation): JsonResponse
    {
        $user = $request->user();
        setPermissionsTeamId($tenant->id);
        $user->unsetRelation('roles');
        if (! $user->hasAnyRole(['owner', 'admin'])) {
            abort(403);
        }

        $updated = $this->invitationService->updateRole(
            $invitation,
            TenantRole::from($request->validated('role'))
        );

        if (! $updated) {
            return response()->json(['error' => 'Invitation is no longer pending.'], 409);
        }

        return response()->json(['data' => $invitation->fresh()]);
    }
}
