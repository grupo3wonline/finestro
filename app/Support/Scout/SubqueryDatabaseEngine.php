<?php

declare(strict_types=1);

namespace App\Support\Scout;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\DatabaseEngine;

/**
 * Scout `database` engine that exposes its search query, so callers can use it
 * as an SQL subquery (`whereIn('id', $query->toBase())`) instead of loading
 * every matching key into PHP. The query has no ORDER BY and no limit unless
 * the Scout builder sets one.
 */
class SubqueryDatabaseEngine extends DatabaseEngine
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $builder
     * @return EloquentBuilder<TModel>
     */
    public function searchQuery(Builder $builder): EloquentBuilder
    {
        return $this->buildSearchQuery($builder);
    }
}
