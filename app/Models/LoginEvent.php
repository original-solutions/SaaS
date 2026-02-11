<?php

namespace App\Models;

use App\Enums\LoginEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'event_type',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => LoginEventType::class,
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
