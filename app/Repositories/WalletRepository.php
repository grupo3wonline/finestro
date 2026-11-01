<?php

namespace App\Repositories;

use App\DTOs\WalletDTO;
use App\Interfaces\Repositories\WalletRepositoryInterface;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class WalletRepository implements WalletRepositoryInterface
{
    public function __construct(
        protected Wallet $model
    ) {}

    public function getAll(array $filters = []): Collection
    {
        return $this->model->filter($filters)->orderBy('name')->get();
    }

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->model->filter($filters)->orderBy('name')->paginate($perPage);
    }

    public function findById(string $id): ?Wallet
    {
        return $this->model->find($id);
    }

    public function create(WalletDTO $dto): Wallet
    {
        return $this->model->create($dto->toArray());
    }

    public function update(string $id, WalletDTO $dto): Wallet
    {
        $wallet = $this->findById($id);
        $wallet->update($dto->toArray());

        return $wallet->fresh();
    }

    public function delete(string $id): bool
    {
        $wallet = $this->findById($id);

        return $wallet instanceof Wallet ? $wallet->delete() : false;
    }

    public function getSharedWithUser(string $userId): Collection
    {
        // withoutGlobalScope('user'): the BelongsToUser scope forces
        // "user_id = me", which combined with "user_id != me" is always empty.
        return $this->model->newQuery()
            ->withoutGlobalScope('user')
            ->whereHas('members', fn ($query) => $query->where('user_id', $userId)->whereNotNull('accepted_at'))
            ->where('user_id', '!=', $userId)
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    public function findAccessibleById(string $id, string $userId): ?Wallet
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('user')
            ->whereKey($id)
            ->where(fn ($query) => $query
                ->where('user_id', $userId)
                ->orWhereHas('members', fn ($members) => $members->where('user_id', $userId)->whereNotNull('accepted_at')))
            ->first();
    }

    public function namesForUser(string $userId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->model->newQuery()
            ->withoutGlobalScope('user')
            ->withTrashed()
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'deleted_at'])
            ->mapWithKeys(fn (Wallet $wallet): array => [
                (string) $wallet->id => ['name' => (string) $wallet->name, 'deleted' => $wallet->trashed()],
            ])
            ->all();
    }
}
