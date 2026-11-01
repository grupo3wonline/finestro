<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class WalletPolicy extends OwnedResourcePolicy
{
    protected string $permissionPrefix = 'wallets';

    /**
     * Owners and accepted shared-wallet members may view the wallet.
     *
     * @param  Wallet  $model
     */
    public function view(User $user, Model $model): Response
    {
        $permission = $this->permits($user, 'view');

        if ($permission->denied()) {
            return $permission;
        }

        return $model->isOwnedBy($user) || $model->hasAcceptedMember($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Share (invite members): owner only AND the plan must include sharing.
     *
     * Ownership is checked first so a non-owner never learns whether the
     * wallet exists; the plan gate only applies once ownership is confirmed.
     */
    public function share(User $user, Wallet $wallet): Response
    {
        $permission = $this->ownedAndPermitted($user, $wallet, 'update');

        if ($permission->denied()) {
            return $permission;
        }

        return $user->hasFeature('wallet_sharing')
            ? Response::allow()
            : Response::deny('O compartilhamento de carteiras está disponível no plano Profissional. Faça upgrade para convidar outras pessoas.');
    }

    /**
     * Credit-card invoice: owner only.
     */
    public function invoice(User $user, Wallet $wallet): Response
    {
        return $this->ownedAndPermitted($user, $wallet, 'view');
    }

    /**
     * Paying an invoice moves money between wallets: owner only.
     */
    public function payInvoice(User $user, Wallet $wallet): Response
    {
        return $this->ownedAndPermitted($user, $wallet, 'update');
    }
}
