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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Relatórios</h1>
            <p class="text-gray-600 dark:text-gray-400 text-sm">Analise suas finanças por período com gráficos e dados detalhados.</p>
        </div>
        <a href="{{ route('reports.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
            <i class="fas fa-file-csv mr-2"></i> Exportar CSV
        </a>
    </div>

    <!-- Date Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-col sm:flex-row items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Início</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Fim</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
            </div>
            <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-filter mr-1"></i> Filtrar
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Receitas</span>
                <i class="fas fa-arrow-up text-green-500"></i>
            </div>
            <p class="text-2xl font-bold text-green-600">R$ {{ number_format($totalIncome, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Despesas</span>
                <i class="fas fa-arrow-down text-red-500"></i>
            </div>
            <p class="text-2xl font-bold text-red-600">R$ {{ number_format($totalExpenses, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Balanço</span>
                <i class="fas fa-balance-scale text-primary-500"></i>
            </div>
            <p class="text-2xl font-bold {{ $balance >= 0 ? 'text-green-600' : 'text-red-600' }}">R$ {{ number_format($balance, 2, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- By Category -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-tags text-primary-500 mr-2"></i> Por Categoria
                </h2>
            </div>
            <div class="p-6">
                @forelse($byCategory as $categoryName => $data)
                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $categoryName }}</p>
                        <p class="text-xs text-gray-500">{{ $data['count'] }} transação(ões)</p>
                    </div>
                    <span class="font-semibold text-sm {{ $data['type'] === 'income' ? 'text-green-600' : 'text-red-600' }}">
                        R$ {{ number_format($data['total'], 2, ',', '.') }}
                    </span>
                </div>
                @empty
                <p class="text-center text-gray-500 dark:text-gray-400 py-8">Nenhuma transação no período.</p>
                @endforelse
            </div>
        </div>

        <!-- By Wallet -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-wallet text-secondary-500 mr-2"></i> Por Carteira
                </h2>
            </div>
            <div class="p-6">
                @forelse($byWallet as $walletName => $data)
                <div class="py-3 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                    <div class="flex items-center justify-between mb-1">
                        <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $walletName }}</p>
                        <span class="text-xs text-gray-500">{{ $data['count'] }} transação(ões)</span>
                    </div>
                    <div class="flex gap-4 text-xs">
                        <span class="text-green-600"><i class="fas fa-arrow-up mr-1"></i>R$ {{ number_format($data['income'], 2, ',', '.') }}</span>
                        <span class="text-red-600"><i class="fas fa-arrow-down mr-1"></i>R$ {{ number_format($data['expense'], 2, ',', '.') }}</span>
                    </div>
                </div>
                @empty
                <p class="text-center text-gray-500 dark:text-gray-400 py-8">Nenhuma transação no período.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
