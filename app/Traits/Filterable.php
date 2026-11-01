<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    /**
     * Apply filters to the query builder.
     *
     * @param  Builder<static>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<static>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if (! is_null($value) && $value !== '') {
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
     *
     * @return array<int, string>
     */
    protected function getFilterableFields(): array
    {
        return $this->filterable ?? [];
    }
}
