@extends('layouts.app')

@section('title', 'Relatórios')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Relatórios</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="space-y-6">

    {{-- 1. Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-chart-line text-primary-500 mr-2"></i>Relatórios
            </h1>
            <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                Analise suas finanças por período com gráficos e dados detalhados.
            </p>
        </div>
        @if(auth()->user()->hasFeature('reports_export'))
            <a href="{{ route('reports.export', ['start_date' => $startDate, 'end_date' => $endDate, 'wallet' => $walletFilter, 'type' => $typeFilter]) }}"
                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition shadow-sm">
                <i class="fas fa-file-csv mr-2"></i> Exportar CSV
            </a>
        @else
            <a href="{{ route('subscription.plans') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                <i class="fas fa-lock mr-2"></i> Exportar CSV
                <span class="ml-2 text-xs bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 px-2 py-0.5 rounded-full">Premium</span>
            </a>
        @endif
    </div>

    {{-- 2. Advanced Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Início</label>
                <input type="date" name="start_date" value="{{ $startDate }}" autocomplete="off"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Fim</label>
                <input type="date" name="end_date" value="{{ $endDate }}" autocomplete="off"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Carteira</label>
                <select name="wallet" autocomplete="off"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition text-sm">
                    <option value="">Todas</option>
                    @foreach($wallets as $wallet)
                        <option value="{{ $wallet->id }}" {{ $walletFilter == $wallet->id ? 'selected' : '' }}>{{ $wallet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                <select name="type" autocomplete="off"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition text-sm">
                    <option value="">Todos</option>
                    <option value="income" {{ $typeFilter === 'income' ? 'selected' : '' }}>Receita</option>
                    <option value="expense" {{ $typeFilter === 'expense' ? 'selected' : '' }}>Despesa</option>
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
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Receitas</span>
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
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Despesas</span>
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
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Balanço</span>
                <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-balance-scale text-primary-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold {{ $balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                R$ {{ number_format($balance, 2, ',', '.') }}
            </p>
        </div>

        {{-- Total Transações --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Total Transações</span>
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exchange-alt text-purple-500 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($transactionCount, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- 4. Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Line Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-chart-area text-primary-500 mr-2"></i> Receitas vs Despesas por Dia
                </h2>
            </div>
            <div class="p-6">
                <canvas id="dailyChart" height="260"></canvas>
            </div>
        </div>
        {{-- Doughnut Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-chart-pie text-secondary-500 mr-2"></i> Despesas por Categoria
                </h2>
            </div>
            <div class="p-6 flex items-center justify-center">
                <canvas id="categoryChart" height="260"></canvas>
            </div>
        </div>
    </div>

    {{-- 5. Top 5 Maiores Gastos --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-fire text-orange-500 mr-2"></i> Top 5 Maiores Gastos
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Categoria</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Carteira</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($topExpenses as $expense)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ \Carbon\Carbon::parse($expense->date)->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white font-medium">{{ $expense->description }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $expense->category->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $expense->wallet->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-right font-semibold text-red-600 dark:text-red-400">R$ {{ number_format($expense->amount, 2, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-tags text-primary-500 mr-2"></i> Por Categoria
                </h2>
            </div>
            <div class="p-6 space-y-4">
                @forelse($byCategory as $categoryName => $data)
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $categoryName }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $data['count'] }} transação(ões)</p>
                        </div>
                        <span class="font-semibold text-sm {{ $data['type'] === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            R$ {{ number_format($data['total'], 2, ',', '.') }}
                        </span>
                    </div>
                    @php
                        $maxTotal = collect($byCategory)->max('total');
                        $percentage = $maxTotal > 0 ? ($data['total'] / $maxTotal) * 100 : 0;
                    @endphp
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $data['type'] === 'income' ? 'bg-green-500' : 'bg-red-500' }}" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-center text-gray-500 dark:text-gray-400 py-8">Nenhuma transação no período.</p>
                @endforelse
            </div>
        </div>

        {{-- Por Carteira --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-wallet text-secondary-500 mr-2"></i> Por Carteira
                </h2>
            </div>
            <div class="p-6 space-y-4">
                @forelse($byWallet as $walletName => $data)
                <div class="py-3 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                    <div class="flex items-center justify-between mb-1">
                        <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $walletName }}</p>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $data['count'] }} transação(ões)</span>
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
                <p class="text-center text-gray-500 dark:text-gray-400 py-8">Nenhuma transação no período.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 7. Transactions Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-list text-gray-500 mr-2"></i> Últimas Transações
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Categoria</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Carteira</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($transactions->take(20) as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white font-medium">{{ $transaction->description }}</td>
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
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $transaction->category->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $transaction->wallet->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-right font-semibold {{ $transaction->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
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
