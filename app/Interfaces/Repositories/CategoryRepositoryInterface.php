<?php

namespace App\Interfaces\Repositories;

use App\DTOs\CategoryDTO;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function findById(string $id): ?Category;

    public function create(CategoryDTO $dto): Category;

    public function update(string $id, CategoryDTO $dto): Category;

    public function delete(string $id): bool;
}
