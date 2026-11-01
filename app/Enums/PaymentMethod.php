<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Formas de pagamento.
 * Origem: lista padrão de `formas_pagamento` do ebfinance.
 */
enum PaymentMethod: string
{
    case PIX = 'pix';
    case CASH = 'cash';
    case DEBIT_CARD = 'debit_card';
    case CREDIT_CARD = 'credit_card';
    case BOLETO = 'boleto';
    case TRANSFER = 'transfer';
    case AUTO_DEBIT = 'auto_debit';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PIX => 'Pix',
            self::CASH => 'Dinheiro',
            self::DEBIT_CARD => 'Cartão de Débito',
            self::CREDIT_CARD => 'Cartão de Crédito',
            self::BOLETO => 'Boleto',
            self::TRANSFER => 'Transferência (TED/DOC)',
            self::AUTO_DEBIT => 'Débito Automático',
            self::OTHER => 'Outro',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PIX => 'fa-bolt',
            self::CASH => 'fa-money-bill-wave',
            self::DEBIT_CARD => 'fa-credit-card',
            self::CREDIT_CARD => 'fa-credit-card',
            self::BOLETO => 'fa-barcode',
            self::TRANSFER => 'fa-building-columns',
            self::AUTO_DEBIT => 'fa-rotate',
            self::OTHER => 'fa-ellipsis',
        };
    }

    /** @return array<string, string> value => label (para <select>) */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
