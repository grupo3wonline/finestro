<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\Repositories\AdminUserRepositoryInterface;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Builder;

class AdminUserRepository implements AdminUserRepositoryInterface
{
    public function query(): Builder
    {
        return User::query()->with(['roles', 'subscription']);
    }

    public function countAll(): int
    {
        return User::query()->count();
    }

    public function countCreatedSince(CarbonInterface $since): int
    {
        return User::query()->where('created_at', '>=', $since)->count();
    }

    public function countProfessional(): int
    {
        return User::query()
            ->whereHas('subscription', fn (Builder $query) => $query->grantingProfessional())
            ->count();
    }

    public function countActiveAdmins(?string $exceptUserId = null): int
    {
        return User::query()
            ->role(RolesAndPermissionsSeeder::ROLE_ADMIN)
            ->whereNull('suspended_at')
            ->when($exceptUserId !== null, fn (Builder $query) => $query->whereKeyNot($exceptUserId))
            ->count();
    }

    public function searchOptions(string $term, int $limit = 50): array
    {
        $escaped = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($term)).'%';

        return User::withTrashed()
            ->where(fn (Builder $query) => $query
                ->where('name', 'like', $escaped)
                ->orWhere('email', 'like', $escaped))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn (User $user): array => [(string) $user->id => $this->label($user)])
            ->all();
    }

    public function optionLabel(string $id): ?string
    {
        $user = User::withTrashed()->find($id, ['id', 'name', 'email']);

        return $user instanceof User ? $this->label($user) : null;
    }

    private function label(User $user): string
    {
        return "{$user->name} ({$user->email})";
    }
}
