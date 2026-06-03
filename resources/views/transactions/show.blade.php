@extends('layouts.app')

@section('title', 'Detalhes da Transação')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('transactions.index') }}" class="text-gray-500 hover:text-primary-600">Transações</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Detalhes</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $transaction->description }}</h1>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $transaction->type === 'income' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                {{ $transaction->type === 'income' ? 'Receita' : 'Despesa' }}
            </span>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Valor</p>
                <p class="text-2xl font-bold {{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                    {{ $transaction->type === 'income' ? '+' : '-' }} R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                </p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Data</p>
                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ $transaction->date->format('d/m/Y') }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Carteira</p>
                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ $transaction->wallet->name ?? '-' }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Categoria</p>
                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ $transaction->category->name ?? 'Sem categoria' }}</p>
            </div>
        </div>

        @if($transaction->notes)
        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Observações</p>
            <p class="text-gray-700 dark:text-gray-300">{{ $transaction->notes }}</p>
        </div>
        @endif

        <div class="flex gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('transactions.edit', $transaction) }}" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-edit mr-1"></i> Editar
            </a>
            <a href="{{ route('transactions.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">Voltar</a>
        </div>
    </div>
</div>
@endsection
