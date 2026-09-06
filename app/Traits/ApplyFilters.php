<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;


trait ApplyFilters
{

    /**
     * Applies filters to the query builder based on the provided data.
     */
    public function filterData(Builder $builder, array $data = []): Builder
    {
        foreach ($data as $column => $value) {
            if (filled($value)) {
                $builder->where($column, 'like', "%$value%");
            }
        }
        return $builder;
    }


    /**
     * Applies sorting to the query builder based on the provided data and allowed columns.
     */
    public function sortData(Builder $builder, array $data = [], array $allowColumns = []): Builder
    {
        $sortBy = $data['sort_by'] ?? 'created_at';
        $sortDirection = $data['sort_direction'] ?? 'desc';
        if (
            in_array($sortBy, $allowColumns, true) &&
            in_array($sortDirection, ['asc', 'desc'], true)
        ) {
            $builder->orderBy($sortBy, $sortDirection);
        }
        return $builder;
    }
}
