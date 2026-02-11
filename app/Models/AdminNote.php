<?php

namespace App\Models;

use App\Enums\AdminNoteTargetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AdminNote extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'target_type',
        'target_id',
        'note',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'target_type' => AdminNoteTargetType::class,
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the target model (polymorphic manual).
     */
    public function target(): BelongsTo
    {
        return match ($this->target_type) {
            AdminNoteTargetType::User => $this->belongsTo(User::class, 'target_id'),
            AdminNoteTargetType::Tenant => $this->belongsTo(Tenant::class, 'target_id'),
            default => $this->belongsTo(User::class, 'target_id'),
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
