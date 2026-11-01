<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\Exceptions\AccountDeletionException;
use App\Models\User;

interface AccountDeletionServiceInterface
{
    /**
     * Cancel any open Stripe subscription, then delete the user.
     * The user is kept when the Stripe cancellation fails. $actor is recorded
     * as the causer of `account_deleted` (an admin deleting the account);
     * when null the authenticated user or the account itself is the causer.
     *
     * @throws AccountDeletionException
     */
    public function delete(User $user, ?User $actor = null): void;
}
