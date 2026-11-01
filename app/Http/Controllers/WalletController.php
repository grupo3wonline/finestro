<?php

namespace App\Http\Controllers;

use App\DTOs\WalletDTO;
use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Interfaces\Services\CreditCardInvoiceServiceInterface;
use App\Interfaces\Services\TransactionServiceInterface;
use App\Interfaces\Services\WalletServiceInterface;
use App\Interfaces\Services\WalletSharingServiceInterface;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WalletController extends Controller
{
    /**
     * Shown when a Free-plan user hits the wallet limit.
     */
    public const string WALLET_LIMIT_MESSAGE = 'Você atingiu o limite de carteiras do plano gratuito. Faça upgrade para o plano Profissional e tenha carteiras ilimitadas.';

    public function __construct(
        protected WalletServiceInterface $walletService,
        protected CreditCardInvoiceServiceInterface $invoiceService,
        protected WalletSharingServiceInterface $sharingService,
        protected TransactionServiceInterface $transactionService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Wallet::class);

        $wallets = $this->walletService->getAllWallets($request->only(['type', 'ownership', 'is_active']));

        // Wallets of other users shared with (and accepted by) this user.
        $sharedWallets = $this->walletService->getSharedWallets($request->user());

        // Invitations awaiting this user's acceptance.
        $pendingInvitations = $this->sharingService->pendingInvitationsFor($request->user());

        return view('wallets.index', ['wallets' => $wallets, 'sharedWallets' => $sharedWallets, 'pendingInvitations' => $pendingInvitations]);
    }

    public function create(): View|RedirectResponse
    {
        $this->authorize('create', Wallet::class);

        $user = auth()->user();
        if (! $user->canCreate('wallet')) {
            return redirect()->route('wallets.index')
                ->with('error', self::WALLET_LIMIT_MESSAGE);
        }

        return view('wallets.create');
    }

    public function store(StoreWalletRequest $request): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->canCreate('wallet')) {
            return redirect()->route('wallets.index')
                ->with('error', self::WALLET_LIMIT_MESSAGE);
        }

        $dto = WalletDTO::fromArray($request->validated());
        $this->walletService->createWallet($dto);

        return redirect()->route('wallets.index')
            ->with('success', 'Carteira criada com sucesso!');
    }

    public function show(Request $request, string $id): View
    {
        $user = $request->user();

        // Owners and accepted shared-wallet members; anyone else gets 404.
        $wallet = $this->walletService->getAccessibleWallet($id, $user) ?? abort(404);
        $this->authorize('view', $wallet);

        $isOwner = $wallet->isOwnedBy($user);

        // On a shared wallet, accepted members see every member's entries;
        // on a private wallet this is simply the owner's own transactions.
        // The service re-asserts access for $user before fanning out.
        $recentTransactions = $this->transactionService
            ->getRecentSharedWalletTransactions($wallet, $user, 10);

        $invoice = $wallet->hasInvoiceCycle() && $user->can('invoice', $wallet)
            ? $this->invoiceService->getInvoice($wallet)
            : null;

        // Sharing is owner-managed. Non-owners viewing a shared wallet do not
        // see the management panel, only their granted read access.
        $members = $isOwner ? $this->sharingService->membersOf($wallet) : collect();
        $canShare = $isOwner && $user->hasFeature('wallet_sharing');

        return view('wallets.show', ['wallet' => $wallet, 'recentTransactions' => $recentTransactions, 'invoice' => $invoice, 'isOwner' => $isOwner, 'members' => $members, 'canShare' => $canShare]);
    }

    public function edit(string $id): View
    {
        $wallet = $this->walletService->getWalletById($id) ?? abort(404);
        $this->authorize('update', $wallet);

        return view('wallets.edit', ['wallet' => $wallet]);
    }

    public function update(UpdateWalletRequest $request, string $id): RedirectResponse
    {
        // Guard: the scoped lookup only finds wallets owned by the current
        // user, so a missing record means it does not exist or is not theirs.
        $wallet = $this->walletService->getWalletById($id) ?? abort(404);
        $this->authorize('update', $wallet);

        $dto = WalletDTO::forUpdate($request->validated());
        $this->walletService->updateWallet($id, $dto);

        return redirect()->route('wallets.index')
            ->with('success', 'Carteira atualizada com sucesso!');
    }

    public function destroy(string $id): RedirectResponse
    {
        $wallet = $this->walletService->getWalletById($id) ?? abort(404);
        $this->authorize('delete', $wallet);

        $this->walletService->deleteWallet($id);

        return redirect()->route('wallets.index')
            ->with('success', 'Carteira excluída com sucesso!');
    }

    /**
     * Fatura do cartão de crédito (ciclo fechamento → vencimento).
     */
    public function invoice(Request $request, string $id): View|RedirectResponse
    {
        $wallet = $this->walletService->getWalletById($id) ?? abort(404);
        $this->authorize('invoice', $wallet);

        if (! $wallet->isCreditCard()) {
            return redirect()->route('wallets.show', $wallet)
                ->with('error', 'Fatura disponível apenas para carteiras do tipo cartão de crédito.');
        }

        if (! $wallet->hasInvoiceCycle()) {
            return redirect()->route('wallets.edit', $wallet)
                ->with('error', 'Informe o dia de fechamento e o dia de vencimento do cartão para ver a fatura.');
        }

        $invoice = $this->invoiceService->getInvoice($wallet, $request->query('ref'));
        $sourceWallets = $this->walletService->getAllWallets()
            ->reject(fn ($w): bool => $w->id === $wallet->id || $w->isCreditCard())
            ->values();

        return view('wallets.invoice', ['wallet' => $wallet, 'invoice' => $invoice, 'sourceWallets' => $sourceWallets]);
    }

    public function payInvoice(Request $request, string $id): RedirectResponse
    {
        $wallet = $this->walletService->getWalletById($id) ?? abort(404);
        $this->authorize('payInvoice', $wallet);
        abort_unless($wallet->isCreditCard(), 404);

        $data = $request->validate([
            'source_wallet_id' => [
                'required', 'uuid',
                Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('deleted_at'),
                Rule::notIn([$wallet->id]),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'ref' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ], [
            'source_wallet_id.required' => 'Selecione a conta de onde sai o pagamento.',
            'source_wallet_id.exists' => 'Conta de origem inválida.',
            'date.before_or_equal' => 'A data de pagamento não pode ser futura.',
        ]);

        $this->invoiceService->payInvoice($wallet, $data['source_wallet_id'], (float) $data['amount'], $data['date'], $data['ref']);

        return redirect()->route('wallets.invoice', ['wallet' => $wallet->id, 'ref' => $data['ref']])
            ->with('success', 'Pagamento da fatura registrado! Os saldos das duas carteiras foram atualizados.');
    }
}
