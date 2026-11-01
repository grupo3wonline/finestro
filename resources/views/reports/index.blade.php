@extends('layouts.app')

@section('title', 'Relatórios')

@section('breadcrumb')
    <x-ui.breadcrumb :items="[['label' => 'Relatórios']]" />
@endsection

@section('content')
<div class="space-y-6">

    {{-- 1. Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-surface-900 dark:text-white">
                <i class="fas fa-chart-line text-primary-500 mr-2"></i>Relatórios
            </h1>
            <p class="text-surface-600 dark:text-surface-400 text-sm mt-1">
                Analise suas finanças por período com gráficos e dados detalhados.
            </p>
        </div>
        @php
            $reportQuery = array_filter([
                'start_date' => $startDate, 'end_date' => $endDate,
                'wallet' => $walletFilter, 'type' => $typeFilter, 'status' => $statusFilter,
            ]);
        @endphp
        @if(auth()->user()->hasFeature('reports_export'))
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('reports.export', $reportQuery) }}" data-no-loading
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition shadow-sm">
                    <i class="fas fa-file-csv mr-2"></i> CSV
                </a>
                <x-ui.button :href="route('reports.export', $reportQuery + ['format' => 'xlsx'])" variant="secondary" size="sm" icon="fa-file-excel" data-no-loading title="Exportar XLSX (Excel) com os mesmos filtros">XLSX</x-ui.button>
                <a href="{{ route('reports.pdf', $reportQuery) }}" data-no-loading
                    title="Baixa o relatório em PDF gerado pelo sistema"
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition shadow-sm">
                    <i class="fas fa-file-pdf mr-2"></i> PDF
                </a>
                <a href="{{ route('reports.print', $reportQuery) }}" target="_blank" rel="noopener"
                    title="Abre versão para impressão (salvar como PDF pelo navegador)"
                    class="inline-flex items-center px-4 py-2 bg-surface-700 hover:bg-surface-800 text-white text-sm font-medium rounded-lg transition shadow-sm">
                    <i class="fas fa-print mr-2"></i> Imprimir
                </a>
                <form action="{{ route('reports.email') }}" method="POST">
                    @csrf
                    @foreach($reportQuery as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <button type="submit" title="Envia o resumo + CSV para {{ auth()->user()->email }}"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition shadow-sm">
                        <i class="fas fa-envelope mr-2"></i> Enviar por e-mail
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('subscription.plans') }}"
                class="inline-flex items-center px-4 py-2 bg-surface-200 dark:bg-surface-800 text-surface-500 dark:text-surface-400 text-sm font-medium rounded-lg hover:bg-surface-300 dark:hover:bg-surface-700 transition">
                <i class="fas fa-lock mr-2"></i> Exportar (CSV, XLSX, PDF, e-mail)
                <span class="ml-2 text-xs bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 px-2 py-0.5 rounded-full">Premium</span>
            </a>
        @endif
    </div>

    {{-- 2. Advanced Filters --}}
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-4">
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Data Início</label>
                <input type="date" name="start_date" value="{{ $startDate }}" autocomplete="off"
                    class="w-full px-3 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Data Fim</label>
                <input type="date" name="end_date" value="{{ $endDate }}" autocomplete="off"
                    class="w-full px-3 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Carteira</label>
                <select name="wallet" autocomplete="off"
                    class="w-full px-3 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition text-sm">
                    <option value="">Todas</option>
                    @foreach($wallets as $wallet)
                        <option value="{{ $wallet->id }}" {{ $walletFilter == $wallet->id ? 'selected' : '' }}>{{ $wallet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Tipo</label>
                <select name="type" autocomplete="off"
                    class="w-full px-3 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition text-sm">
                    <option value="">Todos</option>
                    <option value="income" {{ $typeFilter === 'income' ? 'selected' : '' }}>Receita</option>
                    <option value="expense" {{ $typeFilter === 'expense' ? 'selected' : '' }}>Despesa</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Situação</label>
                <select name="status" autocomplete="off"
                    class="w-full px-3 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition text-sm">
                    <option value="">Todas</option>
                    <option value="paid" {{ $statusFilter === 'paid' ? 'selected' : '' }}>Realizadas</option>
                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pendentes</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                    <i class="fas fa-filter mr-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    {{-- 3. Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Receitas --}}
        <div class="bg-white dark:bg-surface-900 rounded-xl p-5 shadow-sm border border-surface-100 dark:border-surface-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-surface-500 dark:text-surface-400">Receitas</span>
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-arrow-up text-green-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">R$ {{ number_format($totalIncome, 2, ',', '.') }}</p>
            @if($incomeChange !== null)
                <span class="inline-flex items-center text-xs mt-2 px-2 py-0.5 rounded-full {{ $incomeChange >= 0 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
                    <i class="fas fa-arrow-{{ $incomeChange >= 0 ? 'up' : 'down' }} mr-1 text-[10px]"></i>
                    {{ $incomeChange >= 0 ? '+' : '' }}{{ number_format($incomeChange, 1) }}%
                </span>
            @endif
        </div>

        {{-- Despesas --}}
        <div class="bg-white dark:bg-surface-900 rounded-xl p-5 shadow-sm border border-surface-100 dark:border-surface-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-surface-500 dark:text-surface-400">Despesas</span>
                <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-arrow-down text-red-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">R$ {{ number_format($totalExpenses, 2, ',', '.') }}</p>
            @if($expenseChange !== null)
                <span class="inline-flex items-center text-xs mt-2 px-2 py-0.5 rounded-full {{ $expenseChange <= 0 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
                    <i class="fas fa-arrow-{{ $expenseChange <= 0 ? 'down' : 'up' }} mr-1 text-[10px]"></i>
                    {{ $expenseChange >= 0 ? '+' : '' }}{{ number_format($expenseChange, 1) }}%
                </span>
            @endif
        </div>

        {{-- Balanço --}}
        <div class="bg-white dark:bg-surface-900 rounded-xl p-5 shadow-sm border border-surface-100 dark:border-surface-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-surface-500 dark:text-surface-400">Balanço</span>
                <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-balance-scale text-primary-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold {{ $balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                R$ {{ number_format($balance, 2, ',', '.') }}
            </p>
        </div>

        {{-- Total Transações --}}
        <div class="bg-white dark:bg-surface-900 rounded-xl p-5 shadow-sm border border-surface-100 dark:border-surface-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-surface-500 dark:text-surface-400">Total Transações</span>
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exchange-alt text-purple-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-surface-900 dark:text-white">{{ number_format($transactionCount, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- 4. Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Line Chart --}}
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800">
            <div class="p-6 border-b border-surface-100 dark:border-surface-800">
                <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
                    <i class="fas fa-chart-area text-primary-500 mr-2"></i> Receitas vs Despesas por Dia
                </h2>
            </div>
            <div class="p-6">
                <canvas id="dailyChart" height="260"></canvas>
            </div>
        </div>
        {{-- Doughnut Chart --}}
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800">
            <div class="p-6 border-b border-surface-100 dark:border-surface-800">
                <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
                    <i class="fas fa-chart-pie text-secondary-500 mr-2"></i> Despesas por Categoria
                </h2>
            </div>
            <div class="p-6 flex items-center justify-center">
                <canvas id="categoryChart" height="260"></canvas>
            </div>
        </div>
    </div>

    {{-- 4b. Fixas x Variáveis / Realizado x Pendente / Forma de pagamento --}}
    @php $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.'); @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-6">
            <h2 class="text-base font-semibold text-surface-900 dark:text-white mb-1"><i class="fas fa-thumbtack text-primary-500 mr-2"></i>Fixas × Variáveis</h2>
            <p class="text-xs text-surface-500 mb-4">Fixas = recorrentes ou parceladas.</p>
            @foreach(['expense' => ['Despesas', 'bg-red-500', 'bg-red-200 dark:bg-red-900/40'], 'income' => ['Receitas', 'bg-green-500', 'bg-green-200 dark:bg-green-900/40']] as $type => [$label, $fixedColor, $varColor])
                @php
                    $fv = $fixedVsVariable[$type];
                    $sum = $fv['fixed'] + $fv['variable'];
                    $pct = $sum > 0 ? round($fv['fixed'] / $sum * 100) : 0;
                @endphp
                <div class="mb-4 last:mb-0">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-surface-700 dark:text-surface-300 font-medium">{{ $label }}</span>
                        <span class="text-surface-500">{{ $pct }}% fixas</span>
                    </div>
                    <div class="flex h-2.5 rounded-full overflow-hidden {{ $varColor }}">
                        <div class="{{ $fixedColor }}" style="width: {{ $pct }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-surface-500 mt-1">
                        <span>Fixas {{ $brl($fv['fixed']) }}</span>
                        <span>Variáveis {{ $brl($fv['variable']) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-6">
            <h2 class="text-base font-semibold text-surface-900 dark:text-white mb-1"><i class="fas fa-list-check text-emerald-500 mr-2"></i>Realizado × Pendente</h2>
            <p class="text-xs text-surface-500 mb-4">O que já foi pago/recebido e o que ainda está em aberto.</p>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-surface-600 dark:text-surface-400">Recebido</dt><dd class="font-semibold text-green-600">{{ $brl($statusBreakdown['income']['paid']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-surface-600 dark:text-surface-400">A receber</dt><dd class="font-semibold text-green-600/70">{{ $brl($statusBreakdown['income']['pending']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-surface-600 dark:text-surface-400">Pago</dt><dd class="font-semibold text-red-600">{{ $brl($statusBreakdown['expense']['paid']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-surface-600 dark:text-surface-400">A pagar</dt><dd class="font-semibold text-red-600/70">{{ $brl($statusBreakdown['expense']['pending']) }}</dd></div>
            </dl>
            <a href="{{ route('bills.index') }}" class="inline-block mt-4 text-xs text-primary-600 hover:underline">Ver contas em aberto →</a>
        </div>

        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-6">
            <h2 class="text-base font-semibold text-surface-900 dark:text-white mb-1"><i class="fas fa-credit-card text-secondary-500 mr-2"></i>Despesas por forma de pagamento</h2>
            <p class="text-xs text-surface-500 mb-4">Onde o dinheiro saiu.</p>
            @php $maxPm = collect($byPaymentMethod)->max() ?: 1; @endphp
            @forelse($byPaymentMethod as $method => $total)
                <div class="mb-3 last:mb-0">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-surface-700 dark:text-surface-300">{{ $method }}</span>
                        <span class="text-surface-900 dark:text-white font-medium">{{ $brl($total) }}</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-surface-200 dark:bg-surface-800">
                        <div class="h-1.5 rounded-full bg-secondary-500" style="width: {{ round($total / $maxPm * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-surface-500">Sem despesas no período.</p>
            @endforelse
        </div>
    </div>

    {{-- 5. Top 5 Maiores Gastos --}}
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800">
        <div class="p-6 border-b border-surface-100 dark:border-surface-800">
            <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
                <i class="fas fa-fire text-orange-500 mr-2"></i> Top 5 Maiores Gastos
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-50 dark:bg-surface-800/60">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase tracking-wider">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase tracking-wider">Categoria</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase tracking-wider">Carteira</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-surface-500 dark:text-surface-400 uppercase tracking-wider">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                    @forelse($topExpenses as $expense)
                    <tr class="hover:bg-surface-50 dark:hover:bg-surface-800/30 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-surface-600 dark:text-surface-300">{{ \Carbon\Carbon::parse($expense->date)->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-surface-900 dark:text-white font-medium">{{ $expense->description }}</td>
                        <td class="px-6 py-4 text-surface-600 dark:text-surface-300">{{ $expense->category->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-surface-600 dark:text-surface-300">{{ $expense->wallet->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-right font-semibold text-red-600 dark:text-red-400">R$ {{ number_format($expense->amount, 2, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-surface-500 dark:text-surface-400">
                            <i class="fas fa-inbox text-2xl mb-2 block"></i>
                            Nenhuma despesa no período.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 6. Two Columns: Por Categoria + Por Carteira --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Por Categoria --}}
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800">
            <div class="p-6 border-b border-surface-100 dark:border-surface-800">
                <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
                    <i class="fas fa-tags text-primary-500 mr-2"></i> Por Categoria
                </h2>
            </div>
            <div class="p-6 space-y-4">
                @forelse($byCategory as $categoryName => $data)
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <div>
                            <p class="font-medium text-surface-900 dark:text-white text-sm">{{ $categoryName }}</p>
                            <p class="text-xs text-surface-500 dark:text-surface-400">{{ $data['count'] }} transação(ões)</p>
                        </div>
                        <span class="font-semibold text-sm {{ $data['type'] === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            R$ {{ number_format($data['total'], 2, ',', '.') }}
                        </span>
                    </div>
                    @php
                        $maxTotal = collect($byCategory)->max('total');
                        $percentage = $maxTotal > 0 ? ($data['total'] / $maxTotal) * 100 : 0;
                    @endphp
                    <div class="w-full bg-surface-200 dark:bg-surface-800 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $data['type'] === 'income' ? 'bg-green-500' : 'bg-red-500' }}" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-center text-surface-500 dark:text-surface-400 py-8">Nenhuma transação no período.</p>
                @endforelse
            </div>
        </div>

        {{-- Por Carteira --}}
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800">
            <div class="p-6 border-b border-surface-100 dark:border-surface-800">
                <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
                    <i class="fas fa-wallet text-secondary-500 mr-2"></i> Por Carteira
                </h2>
            </div>
            <div class="p-6 space-y-4">
                @forelse($byWallet as $walletName => $data)
                <div class="py-3 {{ !$loop->last ? 'border-b border-surface-100 dark:border-surface-800' : '' }}">
                    <div class="flex items-center justify-between mb-1">
                        <p class="font-medium text-surface-900 dark:text-white text-sm">{{ $walletName }}</p>
                        <span class="text-xs text-surface-500 dark:text-surface-400">{{ $data['count'] }} transação(ões)</span>
                    </div>
                    <div class="flex gap-4 text-xs">
                        <span class="text-green-600 dark:text-green-400">
                            <i class="fas fa-arrow-up mr-1"></i>R$ {{ number_format($data['income'], 2, ',', '.') }}
                        </span>
                        <span class="text-red-600 dark:text-red-400">
                            <i class="fas fa-arrow-down mr-1"></i>R$ {{ number_format($data['expense'], 2, ',', '.') }}
                        </span>
                    </div>
                </div>
                @empty
                <p class="text-center text-surface-500 dark:text-surface-400 py-8">Nenhuma transação no período.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 7. Transactions Table --}}
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800">
        <div class="p-6 border-b border-surface-100 dark:border-surface-800">
            <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
                <i class="fas fa-list text-surface-500 mr-2"></i> Últimas Transações
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-50 dark:bg-surface-800/60">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase tracking-wider">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase tracking-wider">Categoria</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase tracking-wider">Carteira</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-surface-500 dark:text-surface-400 uppercase tracking-wider">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                    @forelse($transactions->take(20) as $transaction)
                    <tr class="hover:bg-surface-50 dark:hover:bg-surface-800/30 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-surface-600 dark:text-surface-300">{{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-surface-900 dark:text-white font-medium">{{ $transaction->description }}</td>
                        <td class="px-6 py-4">
                            @if($transaction->type === 'income')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                    <i class="fas fa-arrow-up mr-1 text-[10px]"></i> Receita
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                    <i class="fas fa-arrow-down mr-1 text-[10px]"></i> Despesa
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-surface-600 dark:text-surface-300">{{ $transaction->category->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-surface-600 dark:text-surface-300">{{ $transaction->wallet->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-right font-semibold {{ $transaction->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-surface-500 dark:text-surface-400">
                            <i class="fas fa-inbox text-2xl mb-2 block"></i>
                            Nenhuma transação no período.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
    const textColor = isDark ? '#9ca3af' : '#6b7280';

    // Line Chart - Receitas vs Despesas por Dia
    const dailyData = @json($dailyData);
    const dailyLabels = Object.keys(dailyData);
    const dailyIncome = dailyLabels.map(d => dailyData[d].income || 0);
    const dailyExpense = dailyLabels.map(d => dailyData[d].expense || 0);

    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: dailyLabels.map(d => {
                const parts = d.split('-');
                return parts[2] + '/' + parts[1];
            }),
            datasets: [
                {
                    label: 'Receitas',
                    data: dailyIncome,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 6
                },
                {
                    label: 'Despesas',
                    data: dailyExpense,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: textColor } }
            },
            scales: {
                x: { ticks: { color: textColor }, grid: { color: gridColor } },
                y: { ticks: { color: textColor }, grid: { color: gridColor } }
            }
        }
    });

    // Doughnut Chart - Despesas por Categoria
    const expenseByCategory = @json($expenseByCategory);
    const categoryLabels = Object.keys(expenseByCategory);
    const categoryValues = Object.values(expenseByCategory);
    const categoryColors = [
        '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
        '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6', '#6366f1',
        '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e'
    ];

    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryValues,
                backgroundColor: categoryColors.slice(0, categoryLabels.length),
                borderWidth: 2,
                borderColor: isDark ? '#1f2937' : '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: textColor, padding: 15, usePointStyle: true }
                }
            }
        }
    });
});
</script>
@endpush
