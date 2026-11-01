<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\Models\FaqCategory;
use Illuminate\Support\Collection;

interface FaqRepositoryInterface
{
    /**
     * Get active FAQ categories with their active items, optionally filtered
     * by a search term, keeping only categories that still have items.
     *
     * @return Collection<int, FaqCategory>
     */
    public function getActiveGroupedByCategory(?string $term = null): Collection;
}
