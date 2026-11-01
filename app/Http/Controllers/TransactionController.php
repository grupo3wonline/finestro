<?php

namespace App\Http\Controllers;

use App\DTOs\TransactionDTO;
use App\Enums\PaymentMethod;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\TransactionIndexRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Interfaces\Services\CategoryServiceInterface;
use App\Interfaces\Services\ReceiptServiceInterface;
use App\Interfaces\Services\TransactionServiceInterface;
use App\Interfaces\Services\WalletServiceInterface;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionServiceInterface $transactionService,
        protected WalletServiceInterface $walletService,
        protected CategoryServiceInterface $categoryService,
        protected ReceiptServiceInterface $receiptService
    ) {}

    /**
     * Listagem com navegação por mês (ideia do ebfinance: "seleção por ano e
     * mês" + balanço mensal), busca textual e filtros de situação/forma de
     * pagamento, paginada.
     */
    public function index(TransactionIndexRequest $request): View
    {
        // viewAny is authorized by TransactionIndexRequest (before validation).
        $year = $request->year();
        $month = $request->month();
        $filters = $request->filters();

        $transactions = $this->transactionService->getPaginatedTransactions($filters, 20);
        $summary = $this->transactionService->getSummary($filters);
        $wallets = $this->walletService->getAllWallets();
        $categories = $this->categoryService->getAllCategories();
        $paymentMethods = PaymentMethod::options();

        // Navegação ← mês anterior | próximo mês →
        $period = $month === 'all' ? null : Carbon::create($year, (int) $month, 1);
        $navigation = [
            'label' => $period instanceof Carbon
                ? ucfirst($period->locale('pt_BR')->translatedFormat('F \d\e Y'))
                : "Ano de {$year}",
            'prev' => $period instanceof Carbon
                ? ['month' => $period->copy()->subMonth()->month, 'year' => $period->copy()->subMonth()->year]
                : ['month' => 'all', 'year' => $year - 1],
            'next' => $period instanceof Carbon
                ? ['month' => $period->copy()->addMonth()->month, 'year' => $period->copy()->addMonth()->year]
                : ['month' => 'all', 'year' => $year + 1],
        ];

        return view('transactions.index', ['transactions' => $transactions, 'summary' => $summary, 'wallets' => $wallets, 'categories' => $categories, 'paymentMethods' => $paymentMethods, 'navigation' => $navigation, 'year' => $year, 'month' => $month]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Transaction::class);

        $wallets = $this->walletService->getAllWallets();
        $categories = $this->categoryService->getAllCategories();
        $paymentMethods = PaymentMethod::options();

        // Permite links como "Nova conta a pagar": ?type=expense&status=pending
        $defaults = [
            'type' => in_array($request->query('type'), ['income', 'expense'], true) ? $request->query('type') : 'expense',
            'status' => $request->query('status') === 'pending' ? 'pending' : 'paid',
            'wallet_id' => $request->query('wallet_id'),
        ];

        return view('transactions.create', ['wallets' => $wallets, 'categories' => $categories, 'paymentMethods' => $paymentMethods, 'defaults' => $defaults]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->canCreate('transaction')) {
            return redirect()->route('transactions.index')
                ->with('error', 'Limite de transações do mês atingido. Faça upgrade para o plano Profissional.');
        }

        // The validated receipt UploadedFile travels in the DTO; the service
        // stores it in the media library.
        $dto = TransactionDTO::fromArray($request->validated());
        $transaction = $this->transactionService->createTransaction($dto);

        $message = $dto->installments > 1
            ? "Compra parcelada em {$transaction->installment_total}x registrada com sucesso!"
            : ($transaction->isPending() ? 'Conta agendada com sucesso!' : 'Transação registrada com sucesso!');

        return redirect()->route('transactions.index', [
            'month' => $transaction->date->month,
            'year' => $transaction->date->year,
        ])->with('success', $message);
    }

    public function show(string $id): View
    {
        $transaction = $this->transactionService->getTransactionById($id) ?? abort(404);
        $this->authorize('view', $transaction);

        $transaction->load(['histories.user', 'installments']);

        return view('transactions.show', ['transaction' => $transaction]);
    }

    public function edit(string $id): View
    {
        $transaction = $this->transactionService->getTransactionById($id) ?? abort(404);
        $this->authorize('update', $transaction);

        $wallets = $this->walletService->getAllWallets();
        $categories = $this->categoryService->getAllCategories();
        $paymentMethods = PaymentMethod::options();

        return view('transactions.edit', ['transaction' => $transaction, 'wallets' => $wallets, 'categories' => $categories, 'paymentMethods' => $paymentMethods]);
    }

    public function update(UpdateTransactionRequest $request, string $id): RedirectResponse
    {
        // Ownership/permission first, so nothing is stored for a foreign id.
        $transaction = $this->transactionService->getTransactionById($id) ?? abort(404);
        $this->authorize('update', $transaction);

        // Sem novo arquivo (e sem remove_receipt), mantém o comprovante atual.
        $this->transactionService->updateTransaction($id, TransactionDTO::fromArray($request->validated()));

        return redirect()->route('transactions.show', $id)
            ->with('success', 'Transação atualizada com sucesso!');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'in:single,installments'],
        ]);

        $transaction = $this->transactionService->getTransactionById($id) ?? abort(404);
        $this->authorize('delete', $transaction);

        if (($data['scope'] ?? 'single') === 'installments' && $transaction->isInstallment()) {
            $count = $this->transactionService->deleteInstallmentGroup($transaction->installment_group_id, $data['reason'] ?? null);

            return redirect()->route('transactions.index')
                ->with('success', "{$count} parcela(s) movida(s) para a lixeira.");
        }

        $this->transactionService->deleteTransaction($id, $data['reason'] ?? null);

        return redirect()->back(fallback: route('transactions.index'))
            ->with('success', 'Transação movida para a lixeira. Você pode restaurá-la quando quiser.');
    }

    /*
    |--------------------------------------------------------------------------
    | Contas a pagar / receber
    |--------------------------------------------------------------------------
    */

    public function pay(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'paid_at' => ['nullable', 'date', 'before_or_equal:today'],
        ], [
            'paid_at.before_or_equal' => 'A data de pagamento não pode ser futura.',
        ]);

        $this->authorize('pay', $this->transactionService->getTransactionById($id) ?? abort(404));

        $transaction = $this->transactionService->markAsPaid($id, $data['paid_at'] ?? null);

        return redirect()->back(fallback: route('bills.index'))
            ->with('success', $transaction->isIncome() ? 'Recebimento confirmado!' : 'Pagamento confirmado!');
    }

    public function unpay(string $id): RedirectResponse
    {
        $this->authorize('unpay', $this->transactionService->getTransactionById($id) ?? abort(404));

        $this->transactionService->markAsPending($id);

        return redirect()->back(fallback: route('bills.index'))
            ->with('success', 'Transação reaberta como pendente. O saldo da carteira foi ajustado.');
    }

    /*
    |--------------------------------------------------------------------------
    | Comprovante e lixeira
    |--------------------------------------------------------------------------
    */

    /**
     * Download/visualização segura do comprovante.
     * getTransactionById já é escopado ao usuário (trait BelongsToUser):
     * o comprovante de outra pessoa resulta em 404.
     */
    public function receipt(string $id): StreamedResponse
    {
        $transaction = $this->transactionService->getTransactionById($id) ?? abort(404);
        $this->authorize('receipt', $transaction);

        return $this->receiptService->response($transaction) ?? abort(404);
    }

    public function trash(): View
    {
        $this->authorize('viewAny', Transaction::class);

        $transactions = $this->transactionService->getTrashedTransactions();

        return view('transactions.trash', ['transactions' => $transactions]);
    }

    public function restore(string $id): RedirectResponse
    {
        $this->authorize('restore', $this->transactionService->getTrashedTransactionById($id) ?? abort(404));

        $transaction = $this->transactionService->restoreTransaction($id);

        return redirect()->route('transactions.show', $transaction->id)
            ->with('success', 'Transação restaurada! O saldo da carteira foi recalculado.');
    }
}
