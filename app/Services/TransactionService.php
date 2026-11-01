<?php

namespace App\Services;

use App\DTOs\TransactionDTO;
use App\Enums\TransactionStatus;
use App\Interfaces\Repositories\TransactionRepositoryInterface;
use App\Interfaces\Repositories\WalletRepositoryInterface;
use App\Interfaces\Services\ReceiptServiceInterface;
use App\Interfaces\Services\TransactionServiceInterface;
use App\Interfaces\Services\UserCacheInterface;
use App\Models\Transaction;
use App\Models\TransactionHistory;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Regras de negócio das transações.
 *
 * Regra central de saldo: SOMENTE transações com status "paid" afetam o saldo
 * da carteira. Uma conta pendente (a pagar/receber) passa a afetar o saldo
 * no momento em que é marcada como paga — e deixa de afetar se for reaberta
 * ou excluída.
 *
 * Toda operação que mexe em mais de uma linha (transação + saldo + histórico,
 * ou N parcelas) roda dentro de DB::transaction(): ou tudo é gravado, ou nada.
 */
class TransactionService implements TransactionServiceInterface
{
    /** Limite de segurança para parcelamentos. */
    public const MAX_INSTALLMENTS = 120;

    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository,
        protected WalletRepositoryInterface $walletRepository,
        protected ReceiptServiceInterface $receipts,
        protected UserCacheInterface $userCache
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Consultas
    |--------------------------------------------------------------------------
    */

    public function getAllTransactions(array $filters = []): Collection
    {
        return $this->transactionRepository->getAll($filters);
    }

    public function getPaginatedTransactions(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->transactionRepository->getPaginated($filters, $perPage);
    }

    public function getSummary(array $filters = []): array
    {
        // Cached per user (key includes Auth::id()); invalidated by the
        // FlushesUserCache model events. Without a user it runs uncached.
        $userId = Auth::id();

        return $this->userCache->remember(
            $userId === null ? null : (string) $userId,
            'transactions.summary',
            $filters,
            fn (): array => $this->computeSummary($filters),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    protected function computeSummary(array $filters): array
    {
        $base = $this->transactionRepository->filteredQuery($filters)->reorder();

        $rows = (clone $base)
            ->selectRaw('type, status, SUM(amount) as total, COUNT(*) as qty')
            ->groupBy('type', 'status')
            ->toBase()
            ->get();

        $sum = fn (string $type, string $status): float => (float) data_get(
            $rows->first(fn ($r): bool => $r->type === $type && $r->status === $status),
            'total',
            0,
        );

        $incomePaid = $sum('income', TransactionStatus::PAID->value);
        $expensePaid = $sum('expense', TransactionStatus::PAID->value);
        $incomePending = $sum('income', TransactionStatus::PENDING->value);
        $expensePending = $sum('expense', TransactionStatus::PENDING->value);

        return [
            'income_paid' => $incomePaid,
            'expense_paid' => $expensePaid,
            'income_pending' => $incomePending,
            'expense_pending' => $expensePending,
            'income_total' => $incomePaid + $incomePending,
            'expense_total' => $expensePaid + $expensePending,
            'balance_realized' => $incomePaid - $expensePaid,
            'balance_projected' => ($incomePaid + $incomePending) - ($expensePaid + $expensePending),
            'count' => (int) $rows->sum('qty'),
            'overdue_count' => (clone $base)->overdue()->count(),
        ];
    }

    public function getTransactionById(string $id): ?Transaction
    {
        return $this->transactionRepository->findById($id);
    }

    public function getTransactionsByWallet(string $walletId): Collection
    {
        return $this->transactionRepository->getByWallet($walletId);
    }

    public function getRecentSharedWalletTransactions(Wallet $wallet, User $user, int $limit = 10): Collection
    {
        // Re-assert access here instead of trusting the caller: this method
        // bypasses the per-user scope, so authorization must be guaranteed at
        // the boundary that actually fans out across members.
        if (is_null($this->walletRepository->findAccessibleById($wallet->id, $user->id))) {
            throw new AuthorizationException('Você não tem acesso a esta carteira.');
        }

        return $this->transactionRepository->recentForWalletAcrossMembers($wallet->id, $limit);
    }

    public function getTransactionsByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->transactionRepository->getByDateRange($startDate, $endDate);
    }

    public function getTrashedTransactionById(string $id): ?Transaction
    {
        return $this->transactionRepository->findTrashedById($id);
    }

    public function getTrashedTransactions(): Collection
    {
        return $this->transactionRepository->getTrashed();
    }

    /** Horizonte padrão (em dias) para listagem de contas a pagar/receber. */
    public const BILLS_HORIZON_DAYS = 60;

    public function getBills(string $type, int $daysAhead = self::BILLS_HORIZON_DAYS): array
    {
        $today = Carbon::today();
        $pending = $this->transactionRepository->getPendingUntil(
            $type,
            $today->copy()->addDays($daysAhead)->toDateString()
        );

        $groups = [
            'overdue' => new Collection,
            'today' => new Collection,
            'next7' => new Collection,
            'later' => new Collection,
        ];

        foreach ($pending as $transaction) {
            $due = $transaction->effective_due_date;

            $key = match (true) {
                $due->lt($today) => 'overdue',
                $due->isSameDay($today) => 'today',
                $due->lte($today->copy()->addDays(7)) => 'next7',
                default => 'later',
            };

            $groups[$key]->push($transaction);
        }

        return $groups;
    }

    /**
     * Build the full bills view model: grouped bills for the requested type,
     * summed totals per group and pending counts for both types.
     *
     * @return array{type: string, groups: array<string, Collection<int, Transaction>>, totals: \Illuminate\Support\Collection<string, float>, counts: array{expense: int, income: int}}
     */
    public function getBillsSummary(string $type): array
    {
        $otherType = $type === 'income' ? 'expense' : 'income';

        $groups = $this->getBills($type);
        $totals = collect($groups)->map(fn (Collection $items): float => (float) $items->sum('amount'));

        // Os grupos completos só são necessários para o tipo solicitado. Para o
        // outro tipo basta o total pendente, obtido via COUNT no banco (sem
        // materializar os modelos) usando o mesmo horizonte do getBills().
        $thisCount = collect($groups)->sum->count();
        $otherCount = $this->transactionRepository->countPendingUntil(
            $otherType,
            Carbon::today()->copy()->addDays(self::BILLS_HORIZON_DAYS)->toDateString()
        );

        $counts = [
            'expense' => $type === 'expense' ? $thisCount : $otherCount,
            'income' => $type === 'income' ? $thisCount : $otherCount,
        ];

        return ['type' => $type, 'groups' => $groups, 'totals' => $totals, 'counts' => $counts];
    }

    /*
    |--------------------------------------------------------------------------
    | Escrita
    |--------------------------------------------------------------------------
    */

    public function createTransaction(TransactionDTO $dto): Transaction
    {
        /** @var list<Media> $written receipt files stored in this call */
        $written = [];

        try {
            return DB::transaction(function () use ($dto, &$written): Transaction {
                if ($dto->installments > 1) {
                    return $this->createInstallments($dto, $written);
                }

                $transaction = $this->transactionRepository->create($dto);

                // An invalid file throws a ValidationException, rolling back the row.
                if ($dto->receipt instanceof UploadedFile) {
                    $written[] = $this->receipts->attach($transaction, $dto->receipt);
                }

                $this->applyToWallet($transaction);
                $this->record($transaction, TransactionHistory::ACTION_CREATED, null, $dto->reason);

                return $transaction;
            });
        } catch (\Throwable $e) {
            // The rows were rolled back: do not leave orphan files on the disk.
            $this->receipts->discard(...$written);

            throw $e;
        }
    }

    /**
     * Parcelamento (ideia do Financas-master, reescrita).
     *
     * - O usuário informa o VALOR TOTAL; o sistema divide em N parcelas mensais.
     * - A divisão é feita em centavos: a diferença de arredondamento vai para
     *   a última parcela. Ex.: R$ 100,00 em 3x = 33,33 + 33,33 + 33,34
     *   (o sistema legado fazia 100/3 e perdia 1 centavo).
     * - Cartão de crédito: todas as parcelas nascem "pagas", porque a compra
     *   consome o limite do cartão imediatamente (como no banco); cada parcela
     *   cai na fatura do seu mês.
     * - Demais carteiras (carnê, boleto parcelado...): a 1ª parcela usa o
     *   status informado e as seguintes nascem "pendentes" (contas a pagar).
     */
    /**
     * @param  list<Media>  $written  receives the receipt media stored here
     */
    protected function createInstallments(TransactionDTO $dto, array &$written = []): Transaction
    {
        $count = max(1, min($dto->installments, self::MAX_INSTALLMENTS));
        $totalCents = (int) round(((float) $dto->amount) * 100);
        $baseCents = intdiv($totalCents, $count);
        $remainder = $totalCents - ($baseCents * $count);

        $groupId = (string) Str::uuid();
        $firstDate = Carbon::parse($dto->date);
        $firstDue = $dto->dueDate ? Carbon::parse($dto->dueDate) : null;

        $wallet = $dto->walletId ? $this->walletRepository->findById($dto->walletId) : null;
        $isCreditCard = $wallet?->isCreditCard() ?? false;

        $first = null;

        for ($i = 1; $i <= $count; $i++) {
            $cents = $baseCents + ($i === $count ? $remainder : 0);

            $status = match (true) {
                $isCreditCard => TransactionStatus::PAID->value,
                $i === 1 => $dto->status ?? TransactionStatus::PAID->value,
                default => TransactionStatus::PENDING->value,
            };

            $installment = $this->transactionRepository->create($dto->withOverrides([
                'amount' => $cents / 100,
                'date' => $firstDate->copy()->addMonthsNoOverflow($i - 1)->toDateString(),
                'due_date' => $firstDue?->copy()->addMonthsNoOverflow($i - 1)->toDateString(),
                'status' => $status,
                'installment_group_id' => $groupId,
                'installment_number' => $i,
                'installment_total' => $count,
                // Parcelamento e recorrência são mutuamente exclusivos.
                'is_recurring' => false,
                'recurring_frequency' => null,
                // O comprovante fica apenas na 1ª parcela.
                'receipt_path' => $i === 1 ? $dto->receiptPath : null,
            ]));

            $this->applyToWallet($installment, wallet: $wallet);
            $this->record($installment, TransactionHistory::ACTION_CREATED, [
                'installment' => ['old' => null, 'new' => "{$i}/{$count}"],
            ], $dto->reason);

            $first ??= $installment;
        }

        // O comprovante fica apenas na 1ª parcela.
        if ($dto->receipt instanceof UploadedFile) {
            $written[] = $this->receipts->attach($first, $dto->receipt);
        }

        return $first;
    }

    public function updateTransaction(string $id, TransactionDTO $dto): Transaction
    {
        /** @var list<Media> $written receipt files stored in this call */
        $written = [];

        try {
            return $this->runUpdate($id, $dto, $written);
        } catch (\Throwable $e) {
            // The rows were rolled back (old receipt row included, its file is
            // only deleted on commit): drop the new file, keep the old one.
            $this->receipts->discard(...$written);

            throw $e;
        }
    }

    /**
     * @param  list<Media>  $written  receives the receipt media stored here
     */
    protected function runUpdate(string $id, TransactionDTO $dto, array &$written): Transaction
    {
        return DB::transaction(function () use ($id, $dto, &$written): Transaction {
            $old = $this->transactionRepository->findById($id)
                ?? throw (new ModelNotFoundException)->setModel(Transaction::class, [$id]);

            $before = $this->snapshot($old);

            // Desfaz o efeito antigo no saldo e aplica o novo (se estiver pago).
            $this->revertFromWallet($old);
            $transaction = $this->transactionRepository->update($id, $dto);

            if ($dto->removeReceipt) {
                $this->receipts->remove($transaction);

                // Legacy column is cleared; the legacy file itself is kept.
                if (filled($transaction->receipt_path)) {
                    $transaction->forceFill(['receipt_path' => null])->save();
                }
            }

            if ($dto->receipt instanceof UploadedFile) {
                $written[] = $this->receipts->attach($transaction, $dto->receipt);
            }

            $transaction->load('media');
            $this->applyToWallet($transaction);

            $changes = $this->diff($before, $this->snapshot($transaction));

            if ($changes !== [] || $dto->reason) {
                $this->record($transaction, TransactionHistory::ACTION_UPDATED, $changes, $dto->reason);
            }

            return $transaction;
        });
    }

    public function deleteTransaction(string $id, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($id, $reason): bool {
            $transaction = $this->transactionRepository->findById($id);

            if (! $transaction instanceof Transaction) {
                return false;
            }

            $this->revertFromWallet($transaction);
            $this->record($transaction, TransactionHistory::ACTION_DELETED, null, $reason);

            return $this->transactionRepository->delete($id);
        });
    }

    public function deleteInstallmentGroup(string $groupId, ?string $reason = null): int
    {
        return DB::transaction(function () use ($groupId, $reason): int {
            $deleted = 0;

            foreach ($this->transactionRepository->getByInstallmentGroup($groupId) as $installment) {
                if ($this->deleteTransaction($installment->id, $reason)) {
                    $deleted++;
                }
            }

            return $deleted;
        });
    }

    /**
     * Restaura da lixeira (ideia do log `exclusoes` do Financas-master:
     * lá a exclusão era só registrada; aqui ela também pode ser desfeita).
     */
    public function restoreTransaction(string $id): Transaction
    {
        return DB::transaction(function () use ($id) {
            $transaction = $this->transactionRepository->findTrashedById($id)
                ?? throw (new ModelNotFoundException)->setModel(Transaction::class, [$id]);

            $transaction->restore();
            $this->applyToWallet($transaction);
            $this->record($transaction, TransactionHistory::ACTION_RESTORED);

            return $transaction->fresh(['wallet', 'category']);
        });
    }

    /**
     * Marca como paga/recebida (ideia do "marcar-movimento" do ebfinance).
     */
    public function markAsPaid(string $id, ?string $paidAt = null): Transaction
    {
        return DB::transaction(function () use ($id, $paidAt) {
            $transaction = $this->transactionRepository->findById($id)
                ?? throw (new ModelNotFoundException)->setModel(Transaction::class, [$id]);

            if ($transaction->isPaid()) {
                return $transaction;
            }

            $oldStatus = $transaction->status;

            $transaction->update([
                'status' => TransactionStatus::PAID->value,
                'paid_at' => $paidAt ? Carbon::parse($paidAt) : now(),
            ]);

            $this->applyToWallet($transaction);
            $this->record($transaction, TransactionHistory::ACTION_PAID, [
                'status' => ['old' => $oldStatus, 'new' => TransactionStatus::PAID->value],
            ]);

            return $transaction->fresh(['wallet', 'category']);
        });
    }

    public function markAsPending(string $id): Transaction
    {
        return DB::transaction(function () use ($id) {
            $transaction = $this->transactionRepository->findById($id)
                ?? throw (new ModelNotFoundException)->setModel(Transaction::class, [$id]);

            // Só há efeito de saldo a reverter se a transação estava paga.
            if (! $transaction->isPaid()) {
                return $transaction;
            }

            $oldStatus = $transaction->status;

            $this->revertFromWallet($transaction);
            $transaction->update(['status' => TransactionStatus::PENDING->value]);
            $this->record($transaction, TransactionHistory::ACTION_UNPAID, [
                'status' => ['old' => $oldStatus, 'new' => TransactionStatus::PENDING->value],
            ]);

            return $transaction->fresh(['wallet', 'category']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Saldo das carteiras
    |--------------------------------------------------------------------------
    */

    /**
     * Aplica o efeito da transação no saldo (somente se estiver paga).
     *
     * Usa increment() — um UPDATE atômico "balance = balance + x" no banco —
     * em vez de ler, somar em PHP e gravar. Isso evita perder lançamentos
     * quando duas requisições alteram a mesma carteira ao mesmo tempo.
     */
    protected function applyToWallet(Transaction $transaction, int $direction = 1, ?Wallet $wallet = null): void
    {
        if (! $transaction->isPaid()) {
            return;
        }

        $wallet ??= $this->walletRepository->findById($transaction->wallet_id);

        $wallet?->increment('balance', $transaction->signed_amount * $direction);
    }

    protected function revertFromWallet(Transaction $transaction): void
    {
        $this->applyToWallet($transaction, -1);
    }

    /*
    |--------------------------------------------------------------------------
    | Auditoria
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array<string, mixed>|null  $changes
     */
    protected function record(Transaction $transaction, string $action, ?array $changes = null, ?string $reason = null): void
    {
        TransactionHistory::create([
            'transaction_id' => $transaction->id,
            'user_id' => Auth::id() ?? $transaction->user_id,
            'action' => $action,
            'changes' => $changes ?: null,
            'reason' => $reason,
        ]);
    }

    /**
     * Foto legível dos campos auditados (nomes em vez de UUIDs).
     *
     * @return array<string, mixed>
     */
    protected function snapshot(Transaction $transaction): array
    {
        $transaction->loadMissing(['wallet', 'category']);

        return [
            'description' => $transaction->description,
            'amount' => number_format((float) $transaction->amount, 2, '.', ''),
            'type' => $transaction->type,
            'status' => $transaction->status,
            'date' => $transaction->date->toDateString(),
            'due_date' => $transaction->due_date?->toDateString(),
            'wallet_id' => $transaction->wallet?->name,
            'category_id' => $transaction->category?->name,
            'payment_method' => $transaction->payment_method_label,
            'notes' => $transaction->notes,
            'is_recurring' => (bool) $transaction->is_recurring,
            'recurring_frequency' => $transaction->recurring_frequency,
            'receipt_path' => $transaction->hasReceipt() ? 'anexado' : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{old: mixed, new: mixed}>
     */
    protected function diff(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $field => $new) {
            $old = $before[$field] ?? null;

            if ($old !== $new) {
                $changes[$field] = ['old' => $old, 'new' => $new];
            }
        }

        return $changes;
    }
}
