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
}
