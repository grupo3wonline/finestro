<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\DTOs\TransactionDTO;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface TransactionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Transaction>
     */
    public function getAll(array $filters = []): Collection;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Transaction>
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Query base com todos os filtros da listagem aplicados
     * (type, wallet, category, status, payment_method, month/year, search).
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Transaction>
     */
    public function filteredQuery(array $filters = []): Builder;

    public function findById(string $id): ?Transaction;

    public function findTrashedById(string $id): ?Transaction;

    public function create(TransactionDTO $dto): Transaction;

    public function update(string $id, TransactionDTO $dto): Transaction;

    public function delete(string $id): bool;

    /** @return Collection<int, Transaction> */
    public function getByWallet(string $walletId): Collection;

    /**
     * Recent transactions of a wallet across ALL members (bypasses user scope).
     * For shared-wallet views where access is already authorized upstream.
     *
     * @return Collection<int, Transaction>
     */
    public function recentForWalletAcrossMembers(string $walletId, int $limit = 10): Collection;

    /** @return Collection<int, Transaction> */
    public function getByDateRange(string $startDate, string $endDate): Collection;

    /** @return Collection<int, Transaction> */
    public function getByInstallmentGroup(string $groupId): Collection;

    /**
     * Transações de uma carteira entre duas datas (inclusive), por data asc.
     *
     * @return Collection<int, Transaction>
     */
    public function getByWalletBetween(string $walletId, string $startDate, string $endDate): Collection;

    /** @return Collection<int, Transaction> */
    public function getTrashed(): Collection;

    /**
     * Pendentes de um tipo (income/expense) com vencimento até $until.
     *
     * @return Collection<int, Transaction>
     */
    public function getPendingUntil(string $type, string $until): Collection;

    /**
     * Conta pendentes de um tipo (income/expense) com vencimento até $until,
     * sem materializar os modelos (COUNT no banco).
     */
    public function countPendingUntil(string $type, string $until): int;
}
