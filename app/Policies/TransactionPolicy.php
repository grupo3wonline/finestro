<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TransactionPolicy extends OwnedResourcePolicy
{
    protected string $permissionPrefix = 'transactions';

    public function pay(User $user, Transaction $transaction): Response
    {
        return $this->ownedAndPermitted($user, $transaction, 'update');
    }

    public function unpay(User $user, Transaction $transaction): Response
    {
        return $this->ownedAndPermitted($user, $transaction, 'update');
    }

    public function receipt(User $user, Transaction $transaction): Response
    {
        return $this->ownedAndPermitted($user, $transaction, 'view');
    }
}
