<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\DTOs\TransactionDTO;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TransactionServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Transaction>
     */
    public function getAllTransactions(array $filters = []): Collection;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Transaction>
     */
    public function getPaginatedTransactions(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Totais (receitas, despesas, pendências) para os mesmos filtros da listagem.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function getSummary(array $filters = []): array;

    public function getTransactionById(string $id): ?Transaction;

    /**
     * Cria a transação. Se $dto->installments > 1, gera o parcelamento e
     * retorna a PRIMEIRA parcela.
     */
    public function createTransaction(TransactionDTO $dto): Transaction;

    public function updateTransaction(string $id, TransactionDTO $dto): Transaction;

    public function deleteTransaction(string $id, ?string $reason = null): bool;

    /**
     * Exclui todas as parcelas (ainda não excluídas) do mesmo parcelamento.
     */
    public function deleteInstallmentGroup(string $groupId, ?string $reason = null): int;

    public function restoreTransaction(string $id): Transaction;

    /**
     * Soft-deleted transaction of the current user (null when missing or foreign).
     */
    public function getTrashedTransactionById(string $id): ?Transaction;

    /** @return Collection<int, Transaction> */
    public function getTrashedTransactions(): Collection;

    public function markAsPaid(string $id, ?string $paidAt = null): Transaction;

    public function markAsPending(string $id): Transaction;

    /**
     * Contas a pagar/receber agrupadas: overdue, today, next7, later.
     *
     * @return array<string, Collection<int, Transaction>>
     */
    public function getBills(string $type, int $daysAhead = 60): array;

    /**
     * Build the bills view model (groups + totals + counts) for a type.
     *
     * @return array{type: string, groups: array<string, Collection<int, Transaction>>, totals: \Illuminate\Support\Collection<string, float>, counts: array{expense: int, income: int}}
     */
    public function getBillsSummary(string $type): array;

    /** @return Collection<int, Transaction> */
    public function getTransactionsByWallet(string $walletId): Collection;

    /**
     * Recent transactions of a shared wallet across all members.
     * Access is re-asserted here (owner or accepted member); callers do not
     * need to pre-authorize, and an unauthorized $user triggers an
     * AuthorizationException.
     *
     * @return Collection<int, Transaction>
     */
    public function getRecentSharedWalletTransactions(Wallet $wallet, User $user, int $limit = 10): Collection;

    /** @return Collection<int, Transaction> */
    public function getTransactionsByDateRange(string $startDate, string $endDate): Collection;
}
