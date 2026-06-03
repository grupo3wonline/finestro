<?php

namespace App\Interfaces\Repositories;

use App\DTOs\TransactionDTO;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

interface TransactionRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function findById(string $id): ?Transaction;

    public function create(TransactionDTO $dto): Transaction;

    public function update(string $id, TransactionDTO $dto): Transaction;

    public function delete(string $id): bool;

    public function getByWallet(string $walletId): Collection;

    public function getByDateRange(string $startDate, string $endDate): Collection;
}
