<?php

namespace App\Interfaces\Services;

use App\DTOs\TransactionDTO;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

interface TransactionServiceInterface
{
    public function getAllTransactions(array $filters = []): Collection;

    public function getTransactionById(string $id): ?Transaction;

    public function createTransaction(TransactionDTO $dto): Transaction;

    public function updateTransaction(string $id, TransactionDTO $dto): Transaction;

    public function deleteTransaction(string $id): bool;

    public function getTransactionsByWallet(string $walletId): Collection;

    public function getTransactionsByDateRange(string $startDate, string $endDate): Collection;
}
