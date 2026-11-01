<?php

namespace App\Services;

use App\DTOs\TransactionDTO;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Interfaces\Repositories\TransactionRepositoryInterface;
use App\Interfaces\Services\CreditCardInvoiceServiceInterface;
use App\Interfaces\Services\TransactionServiceInterface;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fatura de cartão de crédito.
 *
 * Ideia original: o Financas-master tinha limite e dia de vencimento por
 * bandeira (colunas `visa`, `master`, `dia_venc_v`...) fixas na tabela de
 * usuários e somava as compras por mês. Aqui o conceito vira genérico: vale
 * para qualquer carteira do tipo "credit" e respeita o ciclo real
 * (fechamento → vencimento), não o mês-calendário.
 */
class CreditCardInvoiceService implements CreditCardInvoiceServiceInterface
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository,
        protected TransactionServiceInterface $transactionService
    ) {}

    public function getInvoice(Wallet $card, ?string $reference = null): ?array
    {
        if (! $card->hasInvoiceCycle()) {
            return null;
        }

        $current = $card->invoiceCycleFor(Carbon::today());

        $cycle = $reference && preg_match('/^\d{4}-\d{2}$/', $reference)
            ? $card->invoiceCycleFor(Carbon::createFromFormat('Y-m-d', "{$reference}-01")->day(min($card->closing_day, 28)))
            : $current;

        $transactions = $this->transactionRepository->getByWalletBetween(
            $card->id,
            $cycle['start']->toDateString(),
            $cycle['end']->toDateString()
        );

        $isPayment = fn ($t): bool => $t->origin === Transaction::ORIGIN_INVOICE_PAYMENT;

        $charges = $transactions->where('type', 'expense');
        // Estornos/créditos lançados no cartão dentro do ciclo (exceto pagamentos de fatura).
        $credits = $transactions->where('type', 'income')->reject($isPayment);
        $payments = $transactions->where('type', 'income')->filter($isPayment);

        $total = round((float) $charges->sum('amount') - (float) $credits->sum('amount'), 2);

        $state = match (true) {
            $cycle['end']->gte(Carbon::today()) => 'open',      // ainda recebendo compras
            $cycle['due']->gte(Carbon::today()) => 'closed',    // fechada, aguardando pagamento
            default => 'past',                                  // vencida/antiga
        };

        return [
            'cycle' => $cycle,
            'reference' => $cycle['end']->format('Y-m'),
            'label' => ucfirst($cycle['end']->copy()->locale('pt_BR')->translatedFormat('F/Y')),
            'previous' => $cycle['end']->copy()->subMonthNoOverflow()->format('Y-m'),
            'next' => $cycle['end']->copy()->addMonthNoOverflow()->format('Y-m'),
            'is_current' => $cycle['end']->equalTo($current['end']),
            'state' => $state,
            'charges' => $charges->values(),
            'credits' => $credits->values(),
            'payments' => $payments->values(),
            'total' => $total,
            'paid' => round((float) $payments->sum('amount'), 2),
        ];
    }

    public function payInvoice(Wallet $card, string $sourceWalletId, float $amount, string $date, string $reference): void
    {
        $label = ucfirst(Carbon::createFromFormat('Y-m-d', "{$reference}-01")->locale('pt_BR')->translatedFormat('F/Y'));

        DB::transaction(function () use ($card, $sourceWalletId, $amount, $date, $label): void {
            // 1) Sai dinheiro da conta de origem...
            $this->transactionService->createTransaction(TransactionDTO::fromArray([
                'wallet_id' => $sourceWalletId,
                'type' => 'expense',
                'status' => TransactionStatus::PAID->value,
                'amount' => $amount,
                'date' => $date,
                'payment_method' => PaymentMethod::TRANSFER->value,
                'description' => "Pagamento da fatura {$card->name} - {$label}",
                'origin' => Transaction::ORIGIN_INVOICE_PAYMENT,
            ]));

            // 2) ...e entra no cartão, abatendo a dívida (saldo negativo).
            $this->transactionService->createTransaction(TransactionDTO::fromArray([
                'wallet_id' => $card->id,
                'type' => 'income',
                'status' => TransactionStatus::PAID->value,
                'amount' => $amount,
                'date' => $date,
                'payment_method' => PaymentMethod::TRANSFER->value,
                'description' => "Pagamento da fatura {$label}",
                'origin' => Transaction::ORIGIN_INVOICE_PAYMENT,
            ]));
        });
    }
}
