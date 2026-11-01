@extends('layouts.app')

@section('title', 'Transações')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-surface-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-surface-400 text-xs"></i></li>
        <li class="text-surface-700 dark:text-surface-300 font-medium">Transações</li>
    </ol>
</nav>
@endsection

@php
    $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $select = 'input text-sm';
    // Mantém os filtros ao navegar entre meses.
    $keep = request()->except(['month', 'year', 'page']);
    $hasFilters = collect(request()->only(['type', 'wallet_id', 'category_id', 'status', 'payment_method', 'search']))->filter()->isNotEmpty();
@endphp

@section('content')
<div class="space-y-6">
    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-surface-900 dark:text-white">Transações</h1>
            <p class="text-surface-500 dark:text-surface-300 text-sm">Receitas, despesas e contas agendadas, mês a mês.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('transactions.trash') }}" class="btn-ghost" title="Transações excluídas">
                <i class="fas fa-trash-can mr-2"></i> Lixeira
            </a>
            <a href="{{ route('transactions.create', ['type' => 'expense', 'status' => 'pending']) }}" class="inline-flex items-center px-3 py-2 border border-primary-600 text-primary-700 dark:text-primary-300 text-sm font-medium rounded-xl hover:bg-primary-50 dark:hover:bg-primary-900/20 transition">
                <i class="fas fa-calendar-plus mr-2"></i> Agendar conta
            </a>
            <a href="{{ route('transactions.create') }}" class="btn-primary">
                <i class="fas fa-plus mr-2"></i> Nova Transação
            </a>
        </div>
    </div>

    {{-- Navegação por mês --}}
    <div class="flex items-center justify-between bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 px-2 py-2">
        <a href="{{ route('transactions.index', array_merge($keep, $navigation['prev'])) }}" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-600 dark:text-surface-300" title="Período anterior" aria-label="Período anterior">
            <i class="fas fa-chevron-left"></i>
        </a>
        <div class="text-center">
            <p class="font-semibold text-surface-900 dark:text-white">{{ $navigation['label'] }}</p>
            <div class="text-xs space-x-2">
                @if($month === 'all')
                    <a href="{{ route('transactions.index', array_merge($keep, ['month' => now()->month, 'year' => now()->year])) }}" class="text-primary-600 hover:underline">ver mês atual</a>
                @else
                    <a href="{{ route('transactions.index', array_merge($keep, ['month' => 'all', 'year' => $year])) }}" class="text-primary-600 hover:underline">ver ano todo</a>
                    @if((int) $month !== now()->month || $year !== now()->year)
                        <a href="{{ route('transactions.index', array_merge($keep, ['month' => now()->month, 'year' => now()->year])) }}" class="text-primary-600 hover:underline">hoje</a>
                    @endif
                @endif
            </div>
        </div>
        <a href="{{ route('transactions.index', array_merge($keep, $navigation['next'])) }}" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-600 dark:text-surface-300" title="Próximo período" aria-label="Próximo período">
            <i class="fas fa-chevron-right"></i>
        </a>
    </div>

    {{-- Resumo (balanço mensal) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-surface-900 rounded-2xl p-4 shadow-card border border-surface-100 dark:border-surface-800">
            <p class="text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Receitas</p>
            <p class="num text-xl font-semibold text-primary-600 dark:text-primary-400 mt-1">{{ $brl($summary['income_paid']) }}</p>
            @if($summary['income_pending'] > 0)
                <p class="text-xs text-surface-500 mt-1">+ {{ $brl($summary['income_pending']) }} a receber</p>
            @endif
        </div>
        <div class="bg-white dark:bg-surface-900 rounded-2xl p-4 shadow-card border border-surface-100 dark:border-surface-800">
            <p class="text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Despesas</p>
            <p class="num text-xl font-semibold text-red-600 mt-1">{{ $brl($summary['expense_paid']) }}</p>
            @if($summary['expense_pending'] > 0)
                <p class="text-xs text-surface-500 mt-1">+ {{ $brl($summary['expense_pending']) }} a pagar</p>
            @endif
        </div>
        <div class="bg-white dark:bg-surface-900 rounded-2xl p-4 shadow-card border border-surface-100 dark:border-surface-800">
            <p class="text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Saldo realizado</p>
            <p class="num text-xl font-semibold mt-1 {{ $summary['balance_realized'] >= 0 ? 'text-surface-900 dark:text-white' : 'text-red-600' }}">{{ $brl($summary['balance_realized']) }}</p>
            <p class="text-xs text-surface-500 mt-1">o que já entrou − o que já saiu</p>
        </div>
        <div class="bg-white dark:bg-surface-900 rounded-2xl p-4 shadow-card border border-surface-100 dark:border-surface-800">
            <p class="text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Saldo previsto</p>
            <p class="num text-xl font-semibold mt-1 {{ $summary['balance_projected'] >= 0 ? 'text-primary-600' : 'text-red-600' }}">{{ $brl($summary['balance_projected']) }}</p>
            @if($summary['overdue_count'] > 0)
                <a href="{{ route('transactions.index', array_merge(request()->query(), ['status' => 'overdue'])) }}" class="inline-flex items-center text-xs text-red-600 mt-1 hover:underline">
                    <i class="fas fa-triangle-exclamation mr-1"></i> {{ $summary['overdue_count'] }} atrasada(s)
                </a>
            @else
                <p class="text-xs text-surface-500 mt-1">incluindo pendentes</p>
            @endif
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-4" x-data="{ more: {{ $hasFilters ? 'true' : 'false' }} }">
        <form method="GET" action="{{ route('transactions.index') }}" class="space-y-3">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">

            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 text-sm z-10"></i>
                    <input type="search" name="search" value="{{ request('search') }}" maxlength="100" placeholder="Buscar na descrição ou observações..."
                        class="{{ $select }} pl-9" autocomplete="off">
                </div>
                <button type="button" @click="more = !more" class="btn-ghost whitespace-nowrap">
                    <i class="fas fa-sliders mr-1"></i> Filtros
                </button>
                <button type="submit" class="btn-primary whitespace-nowrap">
                    <i class="fas fa-filter mr-1"></i> Aplicar
                </button>
                @if($hasFilters)
                    <a href="{{ route('transactions.index', ['month' => $month, 'year' => $year]) }}" class="px-3 py-2 text-sm text-surface-500 hover:text-surface-700 dark:hover:text-surface-300 whitespace-nowrap text-center">Limpar</a>
                @endif
            </div>

            <div x-show="more" x-cloak class="grid grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs font-medium text-surface-500 dark:text-surface-400 mb-1">Tipo</label>
                    <select name="type" class="{{ $select }}">
                        <option value="">Todos</option>
                        <option value="income" @selected(request('type') === 'income')>Receitas</option>
                        <option value="expense" @selected(request('type') === 'expense')>Despesas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-surface-500 dark:text-surface-400 mb-1">Situação</label>
                    <select name="status" class="{{ $select }}">
                        <option value="">Todas</option>
                        <option value="paid" @selected(request('status') === 'paid')>Pagas/recebidas</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pendentes</option>
                        <option value="overdue" @selected(request('status') === 'overdue')>Atrasadas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-surface-500 dark:text-surface-400 mb-1">Carteira</label>
                    <select name="wallet_id" class="{{ $select }}">
                        <option value="">Todas</option>
                        @foreach($wallets as $wallet)
                            <option value="{{ $wallet->id }}" @selected(request('wallet_id') === $wallet->id)>{{ $wallet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-surface-500 dark:text-surface-400 mb-1">Categoria</label>
                    <select name="category_id" class="{{ $select }}">
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2 lg:col-span-1">
                    <label class="block text-xs font-medium text-surface-500 dark:text-surface-400 mb-1">Forma de pagamento</label>
                    <select name="payment_method" class="{{ $select }}">
                        <option value="">Todas</option>
                        @foreach($paymentMethods as $value => $name)
                            <option value="{{ $value }}" @selected(request('payment_method') === $value)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    {{-- Tabela --}}
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 overflow-hidden">
        @if($transactions->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-surface-50 dark:bg-surface-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Descrição</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase hidden md:table-cell">Carteira</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Situação</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Valor</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                    @foreach($transactions as $transaction)
                    <tr class="hover:bg-surface-50 dark:hover:bg-surface-800/40 {{ $transaction->is_overdue ? 'bg-red-50/40 dark:bg-red-900/10' : '' }}">
                        <td class="px-4 py-3 text-sm text-surface-600 dark:text-surface-400 whitespace-nowrap">
                            {{ $transaction->date->format('d/m/Y') }}
                            @if($transaction->isPending() && $transaction->due_date && !$transaction->due_date->equalTo($transaction->date))
                                <span class="block text-[11px] text-surface-400">vence {{ $transaction->due_date->format('d/m') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('transactions.show', $transaction) }}" class="font-medium text-surface-900 dark:text-white hover:text-primary-600">
                                {{ $transaction->description }}
                            </a>
                            <div class="flex flex-wrap items-center gap-1.5 mt-0.5 text-xs text-surface-500 dark:text-surface-400">
                                @if($transaction->category)
                                    <span><i class="fas {{ $transaction->category->icon }} mr-0.5" style="color: {{ $transaction->category->color }}"></i> {{ $transaction->category->name }}</span>
                                @endif
                                @if($transaction->isInstallment())
                                    <span class="px-1.5 rounded bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300" title="Parcela">{{ $transaction->installment_label }}</span>
                                @endif
                                @if($transaction->is_recurring)
                                    <i class="fas fa-rotate text-primary-400" title="Recorrente"></i>
                                @endif
                                @if($transaction->payment_method_label)
                                    <span class="hidden sm:inline">· {{ $transaction->payment_method_label }}</span>
                                @endif
                                @if($transaction->hasReceipt())
                                    <i class="fas fa-paperclip" title="Possui comprovante"></i>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-surface-600 dark:text-surface-400 hidden md:table-cell">{{ $transaction->wallet->name ?? '-' }}</td>
                        <td class="px-4 py-3"><x-tx.status-badge :transaction="$transaction" /></td>
                        <td class="px-4 py-3 num text-sm text-right font-semibold whitespace-nowrap {{ $transaction->isIncome() ? 'text-primary-600 dark:text-primary-400' : 'text-red-600' }} {{ $transaction->isPending() ? 'opacity-70' : '' }}">
                            {{ $transaction->isIncome() ? '+' : '-' }} {{ $brl($transaction->amount) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-0.5">
                                <x-tx.pay-button :transaction="$transaction" compact />
                                <a href="{{ route('transactions.edit', $transaction) }}" class="p-1.5 rounded hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-500" title="Editar">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                                <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" class="inline" onsubmit="return confirm('Mover esta transação para a lixeira?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded hover:bg-surface-100 dark:hover:bg-surface-800 text-red-500" title="Excluir">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-16 px-4">
            <i class="fas fa-receipt text-5xl text-surface-300 dark:text-surface-600 mb-4"></i>
            @if($hasFilters)
                <h3 class="text-lg font-medium text-surface-900 dark:text-white mb-2">Nenhuma transação com esses filtros</h3>
                <a href="{{ route('transactions.index', ['month' => $month, 'year' => $year]) }}" class="text-primary-600 hover:underline text-sm">Limpar filtros</a>
            @else
                <h3 class="text-lg font-medium text-surface-900 dark:text-white mb-2">Nenhuma transação em {{ mb_strtolower($navigation['label']) }}</h3>
                <p class="text-surface-500 dark:text-surface-400 mb-4">Registre receitas, despesas ou agende contas a pagar.</p>
                <a href="{{ route('transactions.create') }}" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i> Nova Transação
                </a>
            @endif
        </div>
        @endif
    </div>

    @if($transactions->hasPages())
        <div>{{ $transactions->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
