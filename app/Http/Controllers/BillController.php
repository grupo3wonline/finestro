<?php

namespace App\Http\Controllers;

use App\Interfaces\Services\TransactionServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Contas a pagar e a receber.
 *
 * Nasce do campo `pago` (s/n) do ebfinance: lá era só um filtro visual; aqui
 * vira uma agenda financeira com atrasadas, vencendo hoje, próximos 7 dias e
 * futuras — e pagar uma conta passa a atualizar o saldo da carteira.
 */
class BillController extends Controller
{
    public function __construct(
        protected TransactionServiceInterface $transactionService
    ) {}

    public function index(Request $request): View
    {
        $type = $request->query('tipo') === 'receber' ? 'income' : 'expense';

        return view('bills.index', $this->transactionService->getBillsSummary($type));
    }
}
