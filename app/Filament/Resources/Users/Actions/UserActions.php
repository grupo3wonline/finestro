<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Actions;

use App\Exceptions\AccountDeletionException;
use App\Exceptions\AdminActionException;
use App\Filament\Resources\Users\UserResource;
use App\Interfaces\Services\AdminUserServiceInterface;
use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Account actions shared by the users table and the user view page. They
 * only call AdminUserServiceInterface (guards and logging live there) and
 * authorize themselves, so a forged Livewire call by a non-admin is refused.
 */
final class UserActions
{
    /**
     * @return list<Action>
     */
    public static function all(bool $redirectAfterDelete = false): array
    {
        return [
            self::promote(),
            self::demote(),
            self::suspend(),
            self::unsuspend(),
            self::resendVerification(),
            self::delete($redirectAfterDelete),
        ];
    }

    public static function promote(): Action
    {
        return self::base('promote', 'Tornar administrador', Heroicon::OutlinedShieldCheck, 'primary')
            ->modalDescription('O usuário passará a acessar o painel administrativo (após configurar a autenticação em dois fatores).')
            ->visible(fn (User $record): bool => ! $record->hasRole('admin'))
            ->action(fn (User $record): bool => self::run(
                fn (AdminUserServiceInterface $service, User $admin) => $service->promote($admin, $record),
                'Usuário promovido a administrador.',
            ));
    }

    public static function demote(): Action
    {
        return self::base('demote', 'Remover administrador', Heroicon::OutlinedShieldExclamation, 'warning')
            ->modalDescription('O usuário perderá o acesso ao painel administrativo.')
            ->visible(fn (User $record): bool => $record->hasRole('admin'))
            ->action(fn (User $record): bool => self::run(
                fn (AdminUserServiceInterface $service, User $admin) => $service->demote($admin, $record),
                'Acesso de administrador removido.',
            ));
    }

    public static function suspend(): Action
    {
        return self::base('suspend', 'Suspender', Heroicon::OutlinedNoSymbol, 'danger')
            ->modalDescription('A conta não poderá entrar no sistema, as sessões abertas serão encerradas e os tokens de API revogados.')
            ->visible(fn (User $record): bool => ! $record->isSuspended())
            ->action(fn (User $record): bool => self::run(
                fn (AdminUserServiceInterface $service, User $admin) => $service->suspend($admin, $record),
                'Conta suspensa.',
            ));
    }

    public static function unsuspend(): Action
    {
        return self::base('unsuspend', 'Reativar', Heroicon::OutlinedCheckCircle, 'success')
            ->modalDescription('A conta voltará a entrar no sistema normalmente.')
            ->visible(fn (User $record): bool => $record->isSuspended())
            ->action(fn (User $record): bool => self::run(
                fn (AdminUserServiceInterface $service, User $admin) => $service->unsuspend($admin, $record),
                'Conta reativada.',
            ));
    }

    public static function resendVerification(): Action
    {
        return self::base('resendVerification', 'Reenviar verificação', Heroicon::OutlinedEnvelope, 'gray')
            ->modalDescription('Um novo link de confirmação será enviado para o e-mail do usuário.')
            ->visible(fn (User $record): bool => ! $record->hasVerifiedEmail())
            ->rateLimit(5)
            ->action(fn (User $record): bool => self::run(
                fn (AdminUserServiceInterface $service, User $admin) => $service->resendVerification($admin, $record),
                'E-mail de verificação reenviado.',
            ));
    }

    public static function delete(bool $redirectAfterDelete = false): Action
    {
        return self::base('deleteAccount', 'Excluir conta', Heroicon::OutlinedTrash, 'danger')
            ->modalHeading('Excluir conta')
            ->modalDescription('A assinatura no Stripe será cancelada e a conta excluída. Os dados financeiros do usuário deixam de ficar acessíveis. Esta ação não pode ser desfeita pelo painel.')
            ->modalSubmitActionLabel('Excluir conta')
            ->action(function (User $record, Action $action) use ($redirectAfterDelete): void {
                $deleted = self::run(
                    fn (AdminUserServiceInterface $service, User $admin) => $service->delete($admin, $record),
                    'Conta excluída.',
                );

                if ($deleted && $redirectAfterDelete) {
                    $action->redirect(UserResource::getUrl('index'));
                }
            });
    }

    private static function base(string $name, string $label, Heroicon $icon, string $color): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->authorize(fn (): bool => UserResource::hasAdminAccess());
    }

    /**
     * Run a service call as the authenticated admin and notify the outcome.
     *
     * @param  Closure(AdminUserServiceInterface, User): void  $call
     */
    private static function run(Closure $call, string $successMessage): bool
    {
        $admin = auth()->user();
        abort_unless($admin instanceof User, 403);

        try {
            $call(app(AdminUserServiceInterface::class), $admin);
        } catch (AdminActionException|AccountDeletionException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return false;
        }

        Notification::make()->success()->title($successMessage)->send();

        return true;
    }
}
