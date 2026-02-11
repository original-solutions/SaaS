<?php

namespace App\Support\Traits;

use App\Models\File;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasFiles
{
    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'fileable')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }
}
