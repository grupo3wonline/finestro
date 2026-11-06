@extends('layouts.app')

@section('title', 'Lixeira de Transações')

@section('breadcrumb')
<x-ui.breadcrumb :items="[
    ['label' => 'Transações', 'url' => route('transactions.index')],
    ['label' => 'Lixeira'],
]" />
@endsection

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-surface-900 dark:text-white"><i class="fas fa-trash-can text-surface-400 mr-2"></i>Lixeira</h1>
        <p class="text-surface-500 dark:text-surface-300 text-sm">Transações excluídas. Ao restaurar, o saldo da carteira é recalculado automaticamente.</p>
    </div>

    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 overflow-hidden">
        @if($transactions->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-surface-50 dark:bg-surface-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Excluída em</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Descrição</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase hidden md:table-cell">Data</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Valor</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                        @foreach($transactions as $transaction)
                            <tr class="hover:bg-surface-50 dark:hover:bg-surface-800/40">
                                <td class="px-4 py-3 num text-sm text-surface-600 dark:text-surface-400 whitespace-nowrap">{{ $transaction->deleted_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-medium text-surface-900 dark:text-white">{{ $transaction->description }}</span>
                                    @if($transaction->isInstallment())
                                        <span class="ml-1 text-xs text-primary-600">{{ $transaction->installment_label }}</span>
                                    @endif
                                    <span class="block text-xs text-surface-500">{{ $transaction->wallet->name ?? 'Carteira removida' }}</span>
                                </td>
                                <td class="px-4 py-3 num text-sm text-surface-600 dark:text-surface-400 hidden md:table-cell">{{ $transaction->date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 num text-sm text-right font-semibold whitespace-nowrap {{ $transaction->isIncome() ? 'text-primary-600 dark:text-primary-400' : 'text-red-600' }}">
                                    {{ $transaction->isIncome() ? '+' : '-' }} R$ {{ number_format((float) $transaction->amount, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <form autocomplete="off" action="{{ route('transactions.restore', $transaction->id) }}" method="POST" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary-700 dark:text-primary-300 border border-primary-300 dark:border-primary-700 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition">
                                            <i class="fas fa-trash-arrow-up mr-1"></i> Restaurar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-16">
                <i class="fas fa-trash-can text-5xl text-surface-300 dark:text-surface-600 mb-4"></i>
                <h3 class="text-lg font-medium text-surface-900 dark:text-white">A lixeira está vazia</h3>
            </div>
        @endif
    </div>
</div>
@endsection
