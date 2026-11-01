<?php

namespace Tests\Feature\Authorization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every authenticated route redirects guests to the login page.
 */
class GuestAccessTest extends TestCase
{
    use RefreshDatabase;

    private const UUID = '00000000-0000-4000-8000-000000000000';

    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, string>}>
     */
    public static function protectedRoutes(): array
    {
        $id = self::UUID;

        return [
            'dashboard' => ['get', 'dashboard', []],
            'profile.edit' => ['get', 'profile.edit', []],
            'settings.index' => ['get', 'settings.index', []],
            'wallets.index' => ['get', 'wallets.index', []],
            'wallets.create' => ['get', 'wallets.create', []],
            'wallets.store' => ['post', 'wallets.store', []],
            'wallets.show' => ['get', 'wallets.show', ['wallet' => $id]],
            'wallets.edit' => ['get', 'wallets.edit', ['wallet' => $id]],
            'wallets.update' => ['put', 'wallets.update', ['wallet' => $id]],
            'wallets.destroy' => ['delete', 'wallets.destroy', ['wallet' => $id]],
            'wallets.invoice' => ['get', 'wallets.invoice', ['wallet' => $id]],
            'wallets.invoice.pay' => ['post', 'wallets.invoice.pay', ['wallet' => $id]],
            'transactions.index' => ['get', 'transactions.index', []],
            'transactions.create' => ['get', 'transactions.create', []],
            'transactions.store' => ['post', 'transactions.store', []],
            'transactions.trash' => ['get', 'transactions.trash', []],
            'transactions.show' => ['get', 'transactions.show', ['transaction' => $id]],
            'transactions.edit' => ['get', 'transactions.edit', ['transaction' => $id]],
            'transactions.update' => ['put', 'transactions.update', ['transaction' => $id]],
            'transactions.destroy' => ['delete', 'transactions.destroy', ['transaction' => $id]],
            'transactions.pay' => ['patch', 'transactions.pay', ['transaction' => $id]],
            'transactions.unpay' => ['patch', 'transactions.unpay', ['transaction' => $id]],
            'transactions.restore' => ['patch', 'transactions.restore', ['transaction' => $id]],
            'transactions.receipt' => ['get', 'transactions.receipt', ['transaction' => $id]],
            'bills.index' => ['get', 'bills.index', []],
            'budgets.index' => ['get', 'budgets.index', []],
            'budgets.store' => ['post', 'budgets.store', []],
            'budgets.update' => ['put', 'budgets.update', ['budget' => $id]],
            'budgets.destroy' => ['delete', 'budgets.destroy', ['budget' => $id]],
            'budgets.copy-previous' => ['post', 'budgets.copy-previous', []],
            'goals.index' => ['get', 'goals.index', []],
            'goals.create' => ['get', 'goals.create', []],
            'goals.store' => ['post', 'goals.store', []],
            'goals.show' => ['get', 'goals.show', ['goal' => $id]],
            'goals.edit' => ['get', 'goals.edit', ['goal' => $id]],
            'goals.update' => ['put', 'goals.update', ['goal' => $id]],
            'goals.destroy' => ['delete', 'goals.destroy', ['goal' => $id]],
            'categories.index' => ['get', 'categories.index', []],
            'categories.create' => ['get', 'categories.create', []],
            'categories.store' => ['post', 'categories.store', []],
            'categories.edit' => ['get', 'categories.edit', ['category' => $id]],
            'categories.update' => ['put', 'categories.update', ['category' => $id]],
            'categories.destroy' => ['delete', 'categories.destroy', ['category' => $id]],
            'categories.import-suggested' => ['post', 'categories.import-suggested', []],
            'reports.index' => ['get', 'reports.index', []],
            'reports.export' => ['get', 'reports.export', []],
            'reports.pdf' => ['get', 'reports.pdf', []],
            'reports.print' => ['get', 'reports.print', []],
            'reports.email' => ['post', 'reports.email', []],
            'subscription.index' => ['get', 'subscription.index', []],
            'subscription.plans' => ['get', 'subscription.plans', []],
            'ai.index' => ['get', 'ai.index', []],
            'ai.settings' => ['get', 'ai.settings', []],
            'ai.credentials.save' => ['post', 'ai.credentials.save', []],
        ];
    }

    #[DataProvider('protectedRoutes')]
    public function test_guest_is_redirected_to_login(string $method, string $name, array $params): void
    {
        $this->{$method}(route($name, $params))->assertRedirect(route('login'));
    }
}
