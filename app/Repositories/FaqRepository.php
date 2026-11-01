<?php

namespace App\Repositories;

use App\Interfaces\Repositories\FaqRepositoryInterface;
use App\Models\FaqCategory;
use Illuminate\Support\Collection;

class FaqRepository implements FaqRepositoryInterface
{
    public function __construct(
        protected FaqCategory $model
    ) {}

    public function getActiveGroupedByCategory(?string $term = null): Collection
    {
        return $this->model->newQuery()
            ->active()
            ->with(['items' => fn ($q) => $q->active()->search($term)])
            ->get()
            ->filter(fn (FaqCategory $c) => $c->items->isNotEmpty())
            ->values();
    }
}
