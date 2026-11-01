<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\Models\Wallet;

interface CreditCardInvoiceServiceInterface
{
    /**
     * Fatura do cartão cujo FECHAMENTO ocorre no mês de referência ("Y-m").
     * Sem referência: fatura aberta atual (a que contém a data de hoje).
     *
     * @return array<string, mixed>|null null se o cartão não tem dias de fechamento/vencimento
     */
    public function getInvoice(Wallet $card, ?string $reference = null): ?array;

    /**
     * Paga a fatura: gera uma despesa na carteira de origem e uma receita
     * no cartão (ambas pagas), zerando a dívida correspondente.
     */
    public function payInvoice(Wallet $card, string $sourceWalletId, float $amount, string $date, string $reference): void;
}
