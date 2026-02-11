<?php

namespace App\Support\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    /**
     * Apply filters from request to query.
     *
     * @param  array<string>  $allowed  Allowed filter keys
     */
    public function scopeApplyFilters(Builder $query, Request $request, array $allowed): Builder
    {
        $filters = $request->input('filters', []);

        foreach ($filters as $key => $value) {
            if (in_array($key, $allowed, true) && $value !== null && $value !== '') {
                $query->where($key, $value);
            }
        }

        return $query;
    }
}
