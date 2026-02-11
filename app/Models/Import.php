<?php

namespace App\Models;

use App\Enums\ImportStatus;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Import extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'type',
        'status',
        'progress',
        'total_rows',
        'processed_rows',
        'error_count',
        'input_file_id',
        'result_file_id',
        'meta',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'progress' => 'integer',
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'error_count' => 'integer',
            'meta' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function inputFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'input_file_id');
    }

    public function resultFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'result_file_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
