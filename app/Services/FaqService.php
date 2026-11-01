<?php

namespace App\Services;

use App\Interfaces\Repositories\FaqRepositoryInterface;
use App\Interfaces\Services\FaqServiceInterface;
use App\Models\FaqCategory;

class FaqService implements FaqServiceInterface
{
    public function __construct(
        protected FaqRepositoryInterface $repository
    ) {}

    public function search(?string $term = null): array
    {
        $term = trim((string) $term);

        $categories = $this->repository->getActiveGroupedByCategory($term);

        $totalResults = $categories->sum(fn (FaqCategory $c) => $c->items->count());

        return ['categories' => $categories, 'term' => $term, 'totalResults' => $totalResults];
    }
}
