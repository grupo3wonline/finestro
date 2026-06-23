<?php

namespace App\Http\Controllers;

use App\DTOs\WalletDTO;
use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Interfaces\Services\WalletServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(
        protected WalletServiceInterface $walletService
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $wallets = $this->walletService->getAllWallets($request->only(['type', 'ownership', 'is_active']));

        // Get shared wallets (business wallets shared with this user)
        $sharedWallets = \App\Models\Wallet::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id)->whereNotNull('accepted_at');
        })->where('user_id', '!=', $user->id)->get();

        return view('wallets.index', compact('wallets', 'sharedWallets'));
    }

    public function create(): View|RedirectResponse
    {
        $user = auth()->user();
        if (!$user->canCreate('wallet')) {
            return redirect()->route('wallets.index')
                ->with('error', 'Você atingiu o limite de carteiras do plano gratuito. Faça upgrade para o plano Profissional.');
        }

        return view('wallets.create');
    }

    public function store(StoreWalletRequest $request): RedirectResponse
    {
        $user = auth()->user();
        if (!$user->canCreate('wallet')) {
            return redirect()->route('wallets.index')
                ->with('error', 'Limite de carteiras atingido. Faça upgrade para o plano Profissional.');
        }

        $dto = WalletDTO::fromArray($request->validated());
        $this->walletService->createWallet($dto);

        return redirect()->route('wallets.index')
            ->with('success', 'Carteira criada com sucesso!');
    }

    public function show(string $id): View
    {
        $wallet = $this->walletService->getWalletById($id);

        if (!$wallet) {
            abort(404);
        }

        return view('wallets.show', compact('wallet'));
    }

    public function edit(string $id): View
    {
        $wallet = $this->walletService->getWalletById($id);

        if (!$wallet) {
            abort(404);
        }

        return view('wallets.edit', compact('wallet'));
    }

    public function update(UpdateWalletRequest $request, string $id): RedirectResponse
    {
        $dto = WalletDTO::fromArray($request->validated());
        $this->walletService->updateWallet($id, $dto);

        return redirect()->route('wallets.index')
            ->with('success', 'Carteira atualizada com sucesso!');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->walletService->deleteWallet($id);

        return redirect()->route('wallets.index')
            ->with('success', 'Carteira excluída com sucesso!');
    }
}
