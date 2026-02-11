<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'plan',
        'trial_ends_at',
        'disabled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'trial_ends_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === TenantStatus::Active;
    }

    /**
     * Check if tenant is disabled.
     */
    public function isDisabled(): bool
    {
        return $this->status === TenantStatus::Disabled;
    }
}
