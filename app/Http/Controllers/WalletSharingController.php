<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteWalletMemberRequest;
use App\Interfaces\Services\WalletServiceInterface;
use App\Interfaces\Services\WalletSharingServiceInterface;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Http\RedirectResponse;

/**
 * Manages the wallet-sharing flow (invite / accept / revoke).
 *
 * SCAFFOLD: wiring and guards are in place; views and the accept landing
 * experience still need to be built. Ownership and plan gating are both
 * delegated to WalletPolicy (see the "share" ability).
 */
class WalletSharingController extends Controller
{
    public function __construct(
        protected WalletServiceInterface $walletService,
        protected WalletSharingServiceInterface $sharingService
    ) {}

    /**
     * Send an invitation to share a wallet with another user.
     */
    public function store(InviteWalletMemberRequest $request, string $wallet): RedirectResponse
    {
        $user = $request->user();

        // Only the owner can invite; a missing/owned-by-others wallet is a 404.
        $walletModel = $this->walletService->getAccessibleWallet($wallet, $user) ?? abort(404);

        // Ownership + plan gating (professional-only) live in WalletPolicy::share.
        $this->authorize('share', $walletModel);

        $this->sharingService->invite(
            $walletModel,
            $user,
            $request->validated('email'),
            $request->validated('role'),
        );

        return redirect()->route('wallets.show', $walletModel)
            ->with('success', 'Convite enviado! A pessoa precisa aceitar para acessar a carteira.');
    }

    /**
     * Accept a pending invitation. Route-model-bound to the current user's membership.
     */
    public function accept(WalletMember $member): RedirectResponse
    {
        abort_unless($member->user_id === auth()->id(), 403);

        $this->sharingService->accept($member, auth()->user());

        return redirect()->route('wallets.show', $member->wallet_id)
            ->with('success', 'Convite aceito! Agora você tem acesso a esta carteira.');
    }

    /**
     * Decline an invitation or remove a membership.
     */
    public function destroy(WalletMember $member): RedirectResponse
    {
        $user = auth()->user();

        // getAccessibleWallet bypasses the user global scope, so the owner is
        // resolvable even though the member row belongs to another user's wallet.
        $wallet = $this->walletService->getAccessibleWallet($member->wallet_id, $user);

        // Either the invited user (decline/leave) or the wallet owner (remove) may revoke.
        abort_unless(
            $member->user_id === $user->id || ($wallet instanceof Wallet && $wallet->isOwnedBy($user)),
            403,
        );

        $this->sharingService->revoke($member);

        return redirect()->back()
            ->with('success', 'Acesso compartilhado removido.');
    }
}
