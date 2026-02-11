<?php

namespace App\Services;

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Notifications\TenantInvitationNotification;
use Illuminate\Support\Str;

class InvitationService
{
    public function __construct(protected MembershipService $membershipService) {}

    /**
     * Create or resend an invitation. If a pending invite exists for (tenant, email), resend it.
     */
    public function invite(Tenant $tenant, string $email, TenantRole $role, User $inviter): TenantInvitation
    {
        $existing = TenantInvitation::query()
            ->where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return $this->resendInvitation($existing);
        }

        $invitation = TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'role' => $role,
            'token' => Str::random(64),
            'invited_by_user_id' => $inviter->id,
            'expires_at' => now()->addDays(config('saas.invite_expiry_days', 7)),
        ]);

        $this->sendInvitationEmail($invitation);

        return $invitation;
    }

    /**
     * Accept an invitation. Validates email match and verified email requirement.
     *
     * @return array{success: bool, error?: string, expected_email?: string}
     */
    public function accept(TenantInvitation $invitation, User $user): array
    {
        if ($invitation->isAccepted()) {
            return ['success' => false, 'error' => 'INVITE_ALREADY_ACCEPTED'];
        }

        if ($invitation->isDeclined()) {
            return ['success' => false, 'error' => 'INVITE_ALREADY_DECLINED'];
        }

        if ($invitation->isExpired()) {
            return ['success' => false, 'error' => 'INVITE_EXPIRED'];
        }

        // Email mismatch check
        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return [
                'success' => false,
                'error' => 'INVITE_EMAIL_MISMATCH',
                'expected_email' => $this->maskEmail($invitation->email),
            ];
        }

        // Require verified email
        if (! $user->hasVerifiedEmail()) {
            return ['success' => false, 'error' => 'EMAIL_NOT_VERIFIED'];
        }

        // Mark as accepted
        $invitation->update(['accepted_at' => now()]);

        // Add membership with correct role
        $this->membershipService->addMember(
            $invitation->tenant,
            $user,
            $invitation->role
        );

        return ['success' => true];
    }

    /**
     * Decline an invitation.
     */
    public function decline(TenantInvitation $invitation): bool
    {
        if (! $invitation->isPending()) {
            return false;
        }

        return $invitation->update(['declined_at' => now()]);
    }

    /**
     * Update the role on a pending invitation.
     */
    public function updateRole(TenantInvitation $invitation, TenantRole $role): bool
    {
        if (! $invitation->isPending()) {
            return false;
        }

        return $invitation->update(['role' => $role]);
    }

    /**
     * Resend an existing invitation — generates new token and resets expiry.
     */
    public function resendInvitation(TenantInvitation $invitation): TenantInvitation
    {
        $invitation->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(config('saas.invite_expiry_days', 7)),
        ]);

        $this->sendInvitationEmail($invitation);

        return $invitation->fresh();
    }

    /**
     * Mask an email for display (e.g., j***@example.com).
     */
    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $local = $parts[0];
        $domain = $parts[1] ?? '';
        $masked = substr($local, 0, 1) . str_repeat('*', max(strlen($local) - 1, 3));

        return $masked . '@' . $domain;
    }

    /**
     * Send the invitation email with a signed link.
     */
    protected function sendInvitationEmail(TenantInvitation $invitation): void
    {
        $user = User::where('email', $invitation->email)->first();

        if ($user) {
            $user->notify(new TenantInvitationNotification($invitation));
        } else {
            // For non-existing users, send via Mail directly
            \Illuminate\Support\Facades\Mail::to($invitation->email)
                ->send(new \App\Mail\TenantInvitationMail($invitation));
        }
    }
}
