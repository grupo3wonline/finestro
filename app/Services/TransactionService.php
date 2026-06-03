<?php

namespace App\Services;

use App\DTOs\TransactionDTO;
use App\Interfaces\Repositories\TransactionRepositoryInterface;
use App\Interfaces\Repositories\WalletRepositoryInterface;
use App\Interfaces\Services\TransactionServiceInterface;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class TransactionService implements TransactionServiceInterface
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository,
        protected WalletRepositoryInterface $walletRepository
    ) {}

    public function getAllTransactions(array $filters = []): Collection
    {
        return $this->transactionRepository->getAll($filters);
    }

    public function getTransactionById(string $id): ?Transaction
    {
        return $this->transactionRepository->findById($id);
    }

    public function createTransaction(TransactionDTO $dto): Transaction
    {
        $transaction = $this->transactionRepository->create($dto);

        // Update wallet balance
        $this->updateWalletBalance($transaction);

        return $transaction;
    }

    public function updateTransaction(string $id, TransactionDTO $dto): Transaction
    {
        $oldTransaction = $this->transactionRepository->findById($id);

        // Reverse old transaction effect on wallet
        $this->reverseWalletBalance($oldTransaction);

        $transaction = $this->transactionRepository->update($id, $dto);

        // Apply new transaction effect on wallet
        $this->updateWalletBalance($transaction);

        return $transaction;
    }

    public function deleteTransaction(string $id): bool
    {
        $transaction = $this->transactionRepository->findById($id);

        if ($transaction) {
            $this->reverseWalletBalance($transaction);
        }

        return $this->transactionRepository->delete($id);
    }

    public function getTransactionsByWallet(string $walletId): Collection
    {
        return $this->transactionRepository->getByWallet($walletId);
    }

    public function getTransactionsByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->transactionRepository->getByDateRange($startDate, $endDate);
    }

    /**
     * Update wallet balance based on transaction.
     */
    protected function updateWalletBalance(Transaction $transaction): void
    {
        $wallet = $this->walletRepository->findById($transaction->wallet_id);

        if ($wallet) {
            $amount = $transaction->type === 'income'
                ? $wallet->balance + $transaction->amount
                : $wallet->balance - $transaction->amount;

            $wallet->update(['balance' => $amount]);
        }
    }

    /**
     * Reverse wallet balance effect of a transaction.
     */
    protected function reverseWalletBalance(Transaction $transaction): void
    {
        $wallet = $this->walletRepository->findById($transaction->wallet_id);

        if ($wallet) {
            $amount = $transaction->type === 'income'
                ? $wallet->balance - $transaction->amount
                : $wallet->balance + $transaction->amount;

            $wallet->update(['balance' => $amount]);
        }
    }
}
