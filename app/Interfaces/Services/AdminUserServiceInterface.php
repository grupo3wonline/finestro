<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\Exceptions\AccountDeletionException;
use App\Exceptions\AdminActionException;
use App\Models\User;

/**
 * Account actions an administrator performs from the admin panel. Every
 * successful action is written to the `security` log with $admin as causer.
 */
interface AdminUserServiceInterface
{
    /** Promotion preconditions, shared with the finestro:make-admin command. */
    public const string PROMOTE_UNVERIFIED_MESSAGE = 'O e-mail deste usuário ainda não foi confirmado. Confirme o e-mail antes de promovê-lo.';

    public const string PROMOTE_SUSPENDED_MESSAGE = 'Esta conta está suspensa e não pode ser promovida.';

    /**
     * Only verified, non-suspended users who are not admins yet (the same
     * rule as finestro:make-admin), so a promotion always grants panel access.
     *
     * @throws AdminActionException
     */
    public function promote(User $admin, User $target): void;

    /**
     * Not on yourself; at least one other active admin must remain.
     *
     * @throws AdminActionException
     */
    public function demote(User $admin, User $target): void;

    /**
     * Not on yourself; never the last active admin. Revokes all API tokens.
     *
     * @throws AdminActionException
     */
    public function suspend(User $admin, User $target): void;

    /**
     * @throws AdminActionException
     */
    public function unsuspend(User $admin, User $target): void;

    /**
     * Queues a new verification e-mail for an unverified account.
     *
     * @throws AdminActionException
     */
    public function resendVerification(User $admin, User $target): void;

    /**
     * Not on yourself; never the last active admin. Cancels Stripe first
     * through the account deletion service.
     *
     * @throws AdminActionException
     * @throws AccountDeletionException
     */
    public function delete(User $admin, User $target): void;
}
