<?php

namespace App\Models;

use App\Enums\RevocationReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'created_ip',
        'user_agent',
        'device_fingerprint',
        'last_active_at',
        'revoked_at',
        'revocation_reason',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
            'revoked_at' => 'datetime',
            'revocation_reason' => RevocationReason::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    /**
     * Scope to active (non-revoked) sessions.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    /**
     * Scope to revoked sessions.
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Revoke this device session.
     */
    public function revoke(RevocationReason $reason): void
    {
        $this->update([
            'revoked_at' => now(),
            'revocation_reason' => $reason,
        ]);

        $this->refreshTokens()->whereNull('revoked_at')->update([
            'revoked_at' => now(),
        ]);
    }

    /**
     * Check if session is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
