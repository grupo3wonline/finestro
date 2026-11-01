<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\Models\User;

interface PersonalDataRepositoryInterface
{
    /**
     * Every stored record about the user, grouped by section (account,
     * wallets, transactions, categories, goals, budgets, subscriptions,
     * payments, receipts, cookie_consents, activity). Soft-deleted rows are
     * included because they are still stored. Never contains passwords,
     * tokens, MFA secrets or AI keys; receipts are metadata only.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function collect(User $user): array;
}
