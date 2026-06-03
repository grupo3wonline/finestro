<?php

namespace App\Interfaces\Services;

use App\DTOs\CategoryDTO;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryServiceInterface
{
    public function getAllCategories(array $filters = []): Collection;

    public function getCategoryById(string $id): ?Category;

    public function createCategory(CategoryDTO $dto): Category;

    public function updateCategory(string $id, CategoryDTO $dto): Category;

    public function deleteCategory(string $id): bool;
}
