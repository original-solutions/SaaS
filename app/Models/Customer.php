<?php

namespace App\Models;

use App\Support\Query\Filterable;
use App\Support\Query\Searchable;
use App\Support\Query\Sortable;
use App\Support\Tenancy\BelongsToTenant;
use App\Support\Traits\HasFiles;
use App\Support\Traits\HasNotes;
use App\Support\Traits\HasTags;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    use BelongsToTenant, Filterable, HasFactory, HasFiles, HasNotes, HasTags, LogsActivity, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'company',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
