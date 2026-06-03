@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Dashboard</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Welcome & Summary -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Olá, {{ Auth::user()->name }}! <span class="text-2xl">👋</span>
            </h1>
            <p class="text-gray-600 dark:text-gray-400">Aqui está o resumo das suas finanças este mês.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('subscription.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium {{ Auth::user()->isProfessional() ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} hover:opacity-80 transition" title="Seu plano atual">
                <i class="fas {{ Auth::user()->isProfessional() ? 'fa-crown text-yellow-500' : 'fa-user' }} mr-1.5"></i>
                {{ Auth::user()->isProfessional() ? 'Profissional' : 'Básico' }}
            </a>
            <a href="{{ route('transactions.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-plus mr-2"></i> Nova Transação
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Balance -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Saldo Total</span>
                <span class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-wallet text-primary-600 text-sm"></i>
                </span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">R$ {{ number_format($totalBalance, 2, ',', '.') }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $wallets->count() }} carteira(s) ativa(s)</p>
        </div>

        <!-- Income -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Receitas do Mês</span>
                <span class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-arrow-up text-green-600 text-sm"></i>
                </span>
            </div>
            <p class="text-2xl font-bold text-green-600">R$ {{ number_format($totalIncome, 2, ',', '.') }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Entradas este mês</p>
        </div>

        <!-- Expenses -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Despesas do Mês</span>
                <span class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-arrow-down text-red-600 text-sm"></i>
                </span>
            </div>
            <p class="text-2xl font-bold text-red-600">R$ {{ number_format($totalExpenses, 2, ',', '.') }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Saídas este mês</p>
        </div>

        <!-- Balance -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Balanço do Mês</span>
                <span class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-pie text-purple-600 text-sm"></i>
                </span>
            </div>
            @php $monthBalance = $totalIncome - $totalExpenses; @endphp
            <p class="text-2xl font-bold {{ $monthBalance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                R$ {{ number_format($monthBalance, 2, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Receitas - Despesas</p>
        </div>
    </div>

    <!-- Wallets & Goals -->
    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Wallets -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-wallet text-primary-500 mr-2"></i> Minhas Carteiras
                </h2>
                <a href="{{ route('wallets.create') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    <i class="fas fa-plus mr-1"></i> Nova
                </a>
            </div>
            <div class="p-6">
                @forelse($wallets as $wallet)
                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: {{ $wallet->color }}20">
                            <i class="fas {{ $wallet->icon }}" style="color: {{ $wallet->color }}"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $wallet->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($wallet->type) }}</p>
                        </div>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white text-sm">
                        R$ {{ number_format($wallet->balance, 2, ',', '.') }}
                    </span>
                </div>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-wallet text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">Nenhuma carteira cadastrada.</p>
                    <a href="{{ route('wallets.create') }}" class="text-primary-600 hover:underline text-sm mt-2 inline-block">Criar primeira carteira</a>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Goals -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-bullseye text-purple-500 mr-2"></i> Metas Ativas
                </h2>
                <a href="{{ route('goals.create') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    <i class="fas fa-plus mr-1"></i> Nova
                </a>
            </div>
            <div class="p-6">
                @forelse($goals as $goal)
                <div class="py-3 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                    <div class="flex items-center justify-between mb-2">
                        <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $goal->name }}</p>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $goal->progress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all" style="width: {{ $goal->progress }}%; background-color: {{ $goal->color }}"></div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-xs text-gray-500">R$ {{ number_format($goal->current_amount, 2, ',', '.') }}</span>
                        <span class="text-xs text-gray-500">R$ {{ number_format($goal->target_amount, 2, ',', '.') }}</span>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-bullseye text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">Nenhuma meta ativa.</p>
                    <a href="{{ route('goals.create') }}" class="text-primary-600 hover:underline text-sm mt-2 inline-block">Criar primeira meta</a>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-exchange-alt text-secondary-500 mr-2"></i> Transações Recentes
            </h2>
            <a href="{{ route('transactions.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Ver todas</a>
        </div>
        <div class="overflow-x-auto">
            @if($transactions->count() > 0)
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Carteira</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($transactions->take(5) as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $transaction->date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $transaction->description }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $transaction->wallet->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-right font-medium {{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $transaction->type === 'income' ? '+' : '-' }} R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center py-12">
                <i class="fas fa-receipt text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">Nenhuma transação registrada este mês.</p>
                <a href="{{ route('transactions.create') }}" class="text-primary-600 hover:underline text-sm mt-2 inline-block">Registrar primeira transação</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
