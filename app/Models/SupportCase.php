<?php

namespace App\Models;

use App\Enums\SupportCasePriority;
use App\Enums\SupportCaseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupportCase extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'subject',
        'status',
        'priority',
        'meta',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupportCaseStatus::class,
            'priority' => SupportCasePriority::class,
            'meta' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
