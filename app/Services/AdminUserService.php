<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AdminActionException;
use App\Interfaces\Repositories\AdminUserRepositoryInterface;
use App\Interfaces\Services\AccountDeletionServiceInterface;
use App\Interfaces\Services\AdminUserServiceInterface;
use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

class AdminUserService implements AdminUserServiceInterface
{
    private const string ROLE = RolesAndPermissionsSeeder::ROLE_ADMIN;

    private const string LAST_ADMIN_MESSAGE = 'É necessário manter pelo menos um administrador ativo.';

    public function __construct(
        protected AdminUserRepositoryInterface $users,
        protected AccountDeletionServiceInterface $accountDeletion,
        protected SecurityActivityLoggerInterface $security,
    ) {}

    public function promote(User $admin, User $target): void
    {
        if ($target->hasRole(self::ROLE)) {
            throw new AdminActionException('Este usuário já é administrador.');
        }

        if (! $target->hasVerifiedEmail()) {
            throw new AdminActionException(self::PROMOTE_UNVERIFIED_MESSAGE);
        }

        if ($target->isSuspended()) {
            throw new AdminActionException(self::PROMOTE_SUSPENDED_MESSAGE);
        }

        $target->assignRole(self::ROLE);

        $this->log('admin_role_assigned', $admin, $target, ['role' => self::ROLE]);
    }

    public function demote(User $admin, User $target): void
    {
        if ($admin->is($target)) {
            throw new AdminActionException('Você não pode remover o seu próprio acesso de administrador.');
        }

        if (! $target->hasRole(self::ROLE)) {
            throw new AdminActionException('Este usuário não é administrador.');
        }

        $this->ensureAnotherActiveAdmin($target);

        $target->removeRole(self::ROLE);

        $this->log('admin_role_removed', $admin, $target, ['role' => self::ROLE]);
    }

    public function suspend(User $admin, User $target): void
    {
        if ($admin->is($target)) {
            throw new AdminActionException('Você não pode suspender a sua própria conta.');
        }

        if ($target->isSuspended()) {
            throw new AdminActionException('Esta conta já está suspensa.');
        }

        if ($target->hasRole(self::ROLE)) {
            $this->ensureAnotherActiveAdmin($target);
        }

        $target->forceFill(['suspended_at' => now()])->save();

        // A suspended account must not keep calling the API.
        $target->tokens()->delete();

        $this->log('admin_user_suspended', $admin, $target);
    }

    public function unsuspend(User $admin, User $target): void
    {
        if (! $target->isSuspended()) {
            throw new AdminActionException('Esta conta não está suspensa.');
        }

        $target->forceFill(['suspended_at' => null])->save();

        $this->log('admin_user_unsuspended', $admin, $target);
    }

    public function resendVerification(User $admin, User $target): void
    {
        if ($target->hasVerifiedEmail()) {
            throw new AdminActionException('Este e-mail já foi verificado.');
        }

        $target->sendEmailVerificationNotification();

        $this->log('admin_verification_resent', $admin, $target);
    }

    public function delete(User $admin, User $target): void
    {
        if ($admin->is($target)) {
            throw new AdminActionException('Você não pode excluir a sua própria conta pelo painel.');
        }

        if ($target->hasRole(self::ROLE)) {
            $this->ensureAnotherActiveAdmin($target);
        }

        // Cancels Stripe first; AccountDeletionException (pt-BR) bubbles up
        // and the account is kept.
        $this->accountDeletion->delete($target, $admin);

        $this->log('admin_user_deleted', $admin, $target);
    }

    private function ensureAnotherActiveAdmin(User $target): void
    {
        if ($this->users->countActiveAdmins($target->getKey()) < 1) {
            throw new AdminActionException(self::LAST_ADMIN_MESSAGE);
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(string $event, User $admin, User $target, array $properties = []): void
    {
        $this->security->log($event, $target, $properties + ['via' => 'admin'], $admin);
    }
}
