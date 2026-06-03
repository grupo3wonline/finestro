<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    /**
     * Apply filters to the query builder.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if (!is_null($value) && $value !== '') {
                if (in_array($field, $this->getFilterableFields())) {
                    $query->where($field, $value);
                }
            }
        }

        return $query;
    }

    /**
     * Get the list of filterable fields.
     * Override in model to define filterable fields.
     */
    protected function getFilterableFields(): array
    {
        return $this->filterable ?? [];
    }
}
