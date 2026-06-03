<?php

namespace App\Http\Controllers;

use App\DTOs\TransactionDTO;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Interfaces\Services\CategoryServiceInterface;
use App\Interfaces\Services\TransactionServiceInterface;
use App\Interfaces\Services\WalletServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionServiceInterface $transactionService,
        protected WalletServiceInterface $walletService,
        protected CategoryServiceInterface $categoryService
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['type', 'wallet_id', 'category_id']);
        $transactions = $this->transactionService->getAllTransactions($filters);
        $wallets = $this->walletService->getAllWallets();
        $categories = $this->categoryService->getAllCategories();

        return view('transactions.index', compact('transactions', 'wallets', 'categories'));
    }

    public function create(): View
    {
        $wallets = $this->walletService->getAllWallets();
        $categories = $this->categoryService->getAllCategories();

        return view('transactions.create', compact('wallets', 'categories'));
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $user = auth()->user();
        if (!$user->canCreate('transaction')) {
            return redirect()->route('transactions.index')
                ->with('error', 'Limite de transações do mês atingido. Faça upgrade para o plano Profissional.');
        }

        $data = $request->validated();

        // Handle receipt upload
        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('receipts', 'private');
        }

        $dto = TransactionDTO::fromArray($data);
        $this->transactionService->createTransaction($dto);

        return redirect()->route('transactions.index')
            ->with('success', 'Transação registrada com sucesso!');
    }

    public function show(string $id): View
    {
        $transaction = $this->transactionService->getTransactionById($id);

        if (!$transaction) {
            abort(404);
        }

        return view('transactions.show', compact('transaction'));
    }

    public function edit(string $id): View
    {
        $transaction = $this->transactionService->getTransactionById($id);
        $wallets = $this->walletService->getAllWallets();
        $categories = $this->categoryService->getAllCategories();

        if (!$transaction) {
            abort(404);
        }

        return view('transactions.edit', compact('transaction', 'wallets', 'categories'));
    }

    public function update(UpdateTransactionRequest $request, string $id): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('receipts', 'private');
        }

        $dto = TransactionDTO::fromArray($data);
        $this->transactionService->updateTransaction($id, $dto);

        return redirect()->route('transactions.index')
            ->with('success', 'Transação atualizada com sucesso!');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->transactionService->deleteTransaction($id);

        return redirect()->route('transactions.index')
            ->with('success', 'Transação excluída com sucesso!');
    }
}
