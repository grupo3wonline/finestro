<?php

namespace App\Services;

use App\DTOs\CategoryDTO;
use App\Interfaces\Repositories\CategoryRepositoryInterface;
use App\Interfaces\Services\CategoryServiceInterface;
use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    public function getAllCategories(array $filters = []): Collection
    {
        return $this->categoryRepository->getAll($filters);
    }

    public function getPaginatedCategories(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->categoryRepository->paginate($filters, $perPage);
    }

    public function getCategoryById(string $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }

    public function createCategory(CategoryDTO $dto): Category
    {
        return $this->categoryRepository->create($dto);
    }

    public function updateCategory(string $id, CategoryDTO $dto): Category
    {
        return $this->categoryRepository->update($id, $dto);
    }

    public function deleteCategory(string $id): bool
    {
        return $this->categoryRepository->delete($id);
    }

    /**
     * Categorias sugeridas.
     * Origem: lista padrão da coluna `categorias` do ebfinance (22 categorias),
     * com ícones Font Awesome e cores atribuídos para o Finestro.
     */
    public function suggestedCategories(): array
    {
        return [
            // Receitas
            ['name' => 'Salário', 'type' => 'income', 'icon' => 'fa-briefcase', 'color' => '#10B981'],
            ['name' => 'Pró-labore', 'type' => 'income', 'icon' => 'fa-user-tie', 'color' => '#059669'],
            ['name' => 'Freelance', 'type' => 'income', 'icon' => 'fa-laptop', 'color' => '#3B82F6'],
            ['name' => 'Lucros', 'type' => 'income', 'icon' => 'fa-chart-line', 'color' => '#0EA5E9'],
            ['name' => 'Rendimentos', 'type' => 'income', 'icon' => 'fa-coins', 'color' => '#14B8A6'],
            ['name' => 'Aluguel recebido', 'type' => 'income', 'icon' => 'fa-building', 'color' => '#22C55E'],
            ['name' => 'Outras Receitas', 'type' => 'income', 'icon' => 'fa-plus-circle', 'color' => '#84CC16'],
            // Despesas
            ['name' => 'Alimentação', 'type' => 'expense', 'icon' => 'fa-utensils', 'color' => '#F59E0B'],
            ['name' => 'Moradia', 'type' => 'expense', 'icon' => 'fa-home', 'color' => '#8B5CF6'],
            ['name' => 'Transporte', 'type' => 'expense', 'icon' => 'fa-car', 'color' => '#EF4444'],
            ['name' => 'Saúde', 'type' => 'expense', 'icon' => 'fa-heart-pulse', 'color' => '#EC4899'],
            ['name' => 'Educação', 'type' => 'expense', 'icon' => 'fa-graduation-cap', 'color' => '#6366F1'],
            ['name' => 'Lazer', 'type' => 'expense', 'icon' => 'fa-gamepad', 'color' => '#06B6D4'],
            ['name' => 'Bem-estar', 'type' => 'expense', 'icon' => 'fa-spa', 'color' => '#A855F7'],
            ['name' => 'Telefonia e Internet', 'type' => 'expense', 'icon' => 'fa-wifi', 'color' => '#64748B'],
            ['name' => 'Impostos e Tarifas', 'type' => 'expense', 'icon' => 'fa-file-invoice-dollar', 'color' => '#DC2626'],
            ['name' => 'Seguros', 'type' => 'expense', 'icon' => 'fa-shield-halved', 'color' => '#0F766E'],
            ['name' => 'Empréstimos', 'type' => 'expense', 'icon' => 'fa-hand-holding-dollar', 'color' => '#B45309'],
            ['name' => 'Investimentos', 'type' => 'expense', 'icon' => 'fa-piggy-bank', 'color' => '#16A34A'],
            ['name' => 'Previdência', 'type' => 'expense', 'icon' => 'fa-umbrella', 'color' => '#0284C7'],
            ['name' => 'Pessoais', 'type' => 'expense', 'icon' => 'fa-user', 'color' => '#F97316'],
            ['name' => 'Outras Despesas', 'type' => 'expense', 'icon' => 'fa-ellipsis', 'color' => '#6B7280'],
        ];
    }

    public function importSuggested(User $user): array
    {
        $existing = Category::where('user_id', $user->id)
            ->get(['name', 'type'])
            ->map(fn ($c) => Str::lower($c->type.'|'.$c->name))
            ->all();

        $imported = 0;
        $skippedByLimit = 0;

        foreach ($this->suggestedCategories() as $suggestion) {
            if (in_array(Str::lower($suggestion['type'].'|'.$suggestion['name']), $existing, true)) {
                continue;
            }

            // Reavalia a cada inserção: o plano grátis tem teto de categorias.
            if (! $user->canCreate('category')) {
                $skippedByLimit++;

                continue;
            }

            $this->categoryRepository->create(CategoryDTO::fromArray(array_merge($suggestion, [
                'user_id' => $user->id,
            ])));
            $imported++;
        }

        return [$imported, $skippedByLimit];
    }
}
