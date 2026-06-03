<?php

namespace App\Repositories;

use App\DTOs\TransactionDTO;
use App\Interfaces\Repositories\TransactionRepositoryInterface;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function __construct(
        protected Transaction $model
    ) {}

    public function getAll(array $filters = []): Collection
    {
        return $this->model
            ->with(['wallet', 'category'])
            ->filter($filters)
            ->orderBy('date', 'desc')
            ->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->model
            ->with(['wallet', 'category'])
            ->filter($filters)
            ->orderBy('date', 'desc')
            ->paginate($perPage);
    }

    public function findById(string $id): ?Transaction
    {
        return $this->model->with(['wallet', 'category'])->find($id);
    }

    public function create(TransactionDTO $dto): Transaction
    {
        return $this->model->create($dto->toArray());
    }

    public function update(string $id, TransactionDTO $dto): Transaction
    {
        $transaction = $this->findById($id);
        $transaction->update($dto->toArray());

        return $transaction->fresh(['wallet', 'category']);
    }

    public function delete(string $id): bool
    {
        $transaction = $this->findById($id);

        return $transaction ? $transaction->delete() : false;
    }

    public function getByWallet(string $walletId): Collection
    {
        return $this->model
            ->where('wallet_id', $walletId)
            ->with(['category'])
            ->orderBy('date', 'desc')
            ->get();
    }

    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['wallet', 'category'])
            ->orderBy('date', 'desc')
            ->get();
    }
}
