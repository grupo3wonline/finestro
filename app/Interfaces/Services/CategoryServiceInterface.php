<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\DTOs\CategoryDTO;
use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CategoryServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Category>
     */
    public function getAllCategories(array $filters = []): Collection;

    /**
     * @param  array<string, mixed>  $filters  supports `type`
     * @return LengthAwarePaginator<int, Category>
     */
    public function getPaginatedCategories(array $filters, int $perPage): LengthAwarePaginator;

    public function getCategoryById(string $id): ?Category;

    public function createCategory(CategoryDTO $dto): Category;

    public function updateCategory(string $id, CategoryDTO $dto): Category;

    public function deleteCategory(string $id): bool;

    /**
     * Lista de categorias sugeridas (nome, tipo, ícone, cor).
     *
     * @return array<int, array{name: string, type: string, icon: string, color: string}>
     */
    public function suggestedCategories(): array;

    /**
     * Importa as sugeridas que o usuário ainda não tem, respeitando o
     * limite do plano. Retorna [importadas, ignoradas por limite].
     *
     * @return array{0: int, 1: int}
     */
    public function importSuggested(User $user): array;
}
