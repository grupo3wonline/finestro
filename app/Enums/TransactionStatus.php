<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Situação de uma transação.
 *
 * - PAID:    efetivada (paga ou recebida). Afeta o saldo da carteira.
 * - PENDING: agendada / em aberto (conta a pagar ou a receber). NÃO afeta o saldo
 *            até ser marcada como paga.
 */
enum TransactionStatus: string
{
    case PAID = 'paid';
    case PENDING = 'pending';

    public function label(string $type = 'expense'): string
    {
        return match ($this) {
            self::PAID => $type === 'income' ? 'Recebido' : 'Pago',
            self::PENDING => $type === 'income' ? 'A receber' : 'A pagar',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
