<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Abilities that can be granted to a personal API token (Sanctum).
 * Routes enforce them with the `abilities:` middleware.
 */
enum ApiTokenAbility: string
{
    case ACCOUNT_READ = 'account:read';
    case WALLETS_READ = 'wallets:read';
    case WALLETS_WRITE = 'wallets:write';
    case TRANSACTIONS_READ = 'transactions:read';
    case TRANSACTIONS_WRITE = 'transactions:write';
    case GOALS_READ = 'goals:read';
    case GOALS_WRITE = 'goals:write';
    case BUDGETS_READ = 'budgets:read';
    case BUDGETS_WRITE = 'budgets:write';
    case CATEGORIES_READ = 'categories:read';
    case CATEGORIES_WRITE = 'categories:write';
    case REPORTS_READ = 'reports:read';

    public function label(): string
    {
        return match ($this) {
            self::ACCOUNT_READ => 'Conta e assinatura (leitura)',
            self::WALLETS_READ => 'Carteiras (leitura)',
            self::WALLETS_WRITE => 'Carteiras (escrita)',
            self::TRANSACTIONS_READ => 'Transações (leitura)',
            self::TRANSACTIONS_WRITE => 'Transações (escrita)',
            self::GOALS_READ => 'Metas (leitura)',
            self::GOALS_WRITE => 'Metas (escrita)',
            self::BUDGETS_READ => 'Orçamentos (leitura)',
            self::BUDGETS_WRITE => 'Orçamentos (escrita)',
            self::CATEGORIES_READ => 'Categorias (leitura)',
            self::CATEGORIES_WRITE => 'Categorias (escrita)',
            self::REPORTS_READ => 'Relatórios (leitura)',
        };
    }

    /** Resource group label (pt-BR), used to group checkboxes in the UI. */
    public function resource(): string
    {
        return match ($this) {
            self::ACCOUNT_READ => 'Conta e assinatura',
            self::WALLETS_READ, self::WALLETS_WRITE => 'Carteiras',
            self::TRANSACTIONS_READ, self::TRANSACTIONS_WRITE => 'Transações',
            self::GOALS_READ, self::GOALS_WRITE => 'Metas',
            self::BUDGETS_READ, self::BUDGETS_WRITE => 'Orçamentos',
            self::CATEGORIES_READ, self::CATEGORIES_WRITE => 'Categorias',
            self::REPORTS_READ => 'Relatórios',
        };
    }

    public function isWrite(): bool
    {
        return str_ends_with($this->value, ':write');
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
