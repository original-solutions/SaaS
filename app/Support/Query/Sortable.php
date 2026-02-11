<?php

namespace App\Support\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Sortable
{
    /**
     * Apply sort from request to query.
     *
     * @param  array<string>  $allowed  Allowed sort columns
     */
    public function scopeApplySort(Builder $query, Request $request, array $allowed, string $default = 'created_at', string $defaultDirection = 'desc'): Builder
    {
        $sort = $request->input('sort', $default);
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (! in_array($column, $allowed, true)) {
            $column = $default;
            $direction = $defaultDirection;
        }

        return $query->orderBy($column, $direction);
    }
}
