<?php

namespace App\Support\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Searchable
{
    /**
     * Apply search from request to query.
     *
     * @param  array<string>  $columns  Columns to search across
     */
    public function scopeApplySearch(Builder $query, Request $request, array $columns): Builder
    {
        $search = $request->input('search');

        if (! $search || empty($columns)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search, $columns): void {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$search}%");
            }
        });
    }
}
