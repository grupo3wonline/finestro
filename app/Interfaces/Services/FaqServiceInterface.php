<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\Models\FaqCategory;
use Illuminate\Support\Collection;

interface FaqServiceInterface
{
    /**
     * Build the FAQ view model: grouped categories, the search term and the
     * total number of matching items.
     *
     * @return array{categories: Collection<int, FaqCategory>, term: string, totalResults: int}
     */
    public function search(?string $term = null): array;
}
