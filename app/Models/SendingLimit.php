<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendingLimit extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'channel',
        'rate_per_minute',
        'daily_cap',
        'sent_today',
        'reset_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reset_date' => 'date',
        ];
    }

    /**
     * Check if the daily cap has been reached.
     */
    public function isAtDailyCap(): bool
    {
        $this->resetIfNewDay();

        return $this->sent_today >= $this->daily_cap;
    }

    /**
     * Increment the daily counter.
     */
    public function incrementSentCount(): void
    {
        $this->resetIfNewDay();
        $this->update(['sent_today' => $this->sent_today + 1]);
    }

    /**
     * Reset counter if a new day.
     */
    private function resetIfNewDay(): void
    {
        if ($this->reset_date === null || $this->reset_date->lt(now()->startOfDay())) {
            $this->update([
                'sent_today' => 0,
                'reset_date' => now()->startOfDay(),
            ]);
            $this->refresh();
        }
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
