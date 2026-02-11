<?php

namespace App\Services;

use App\Models\EmailSuppression;
use App\Models\MessageLog;
use App\Models\SendingLimit;

class DeliverabilityService
{
    /**
     * Check if a message can be sent to the given recipient for the given tenant and channel.
     *
     * @return array{allowed: bool, reason: string|null}
     */
    public function canSend(string $recipient, int $tenantId, string $channel = 'email'): array
    {
        // Check suppression list
        if (EmailSuppression::isSuppressed($recipient, $tenantId)) {
            return ['allowed' => false, 'reason' => 'Email is on the suppression list.'];
        }

        // Check sending limits
        $limit = SendingLimit::query()
            ->where('tenant_id', $tenantId)
            ->where('channel', $channel)
            ->first();

        if ($limit && $limit->isAtDailyCap()) {
            return ['allowed' => false, 'reason' => 'Daily sending cap reached.'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Record a sent message and increment sending limits.
     */
    public function recordSent(
        int $tenantId,
        string $channel,
        string $recipient,
        ?string $subject = null,
        ?string $providerMessageId = null,
    ): MessageLog {
        // Increment sending limit
        $limit = SendingLimit::query()
            ->where('tenant_id', $tenantId)
            ->where('channel', $channel)
            ->first();

        $limit?->incrementSentCount();

        return MessageLog::create([
            'tenant_id' => $tenantId,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'status' => 'sent',
            'provider_message_id' => $providerMessageId,
        ]);
    }

    /**
     * Record a bounced email and optionally suppress it.
     */
    public function recordBounce(
        string $recipient,
        ?int $tenantId = null,
        string $bounceType = 'hard',
        ?string $details = null,
    ): void {
        MessageLog::query()
            ->where('recipient', $recipient)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->latest()
            ->first()
            ?->update(['status' => 'bounced', 'error_message' => $details]);

        // Auto-suppress on hard bounces
        if ($bounceType === 'hard') {
            EmailSuppression::firstOrCreate(
                ['email' => $recipient, 'tenant_id' => $tenantId],
                ['reason' => 'hard_bounce', 'details' => $details],
            );
        }
    }

    /**
     * Record a complaint (e.g., spam report) and suppress.
     */
    public function recordComplaint(string $recipient, ?int $tenantId = null, ?string $details = null): void
    {
        EmailSuppression::firstOrCreate(
            ['email' => $recipient, 'tenant_id' => $tenantId],
            ['reason' => 'complaint', 'details' => $details],
        );

        MessageLog::query()
            ->where('recipient', $recipient)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->latest()
            ->first()
            ?->update(['status' => 'complained']);
    }

    /**
     * Remove an email from the suppression list.
     */
    public function unsuppress(string $email, ?int $tenantId = null): bool
    {
        return EmailSuppression::query()
            ->where('email', $email)
            ->where('tenant_id', $tenantId)
            ->delete() > 0;
    }
}
