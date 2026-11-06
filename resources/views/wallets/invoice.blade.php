@extends('layouts.app')

@section('title', 'Fatura - ' . $wallet->name)

@section('breadcrumb')
<x-ui.breadcrumb :items="[
    ['label' => 'Carteiras', 'url' => route('wallets.index')],
    ['label' => $wallet->name, 'url' => route('wallets.show', $wallet)],
    ['label' => 'Fatura'],
]" />
@endsection

@php
    $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $states = [
        'open' => ['label' => 'Aberta', 'class' => 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'],
        'closed' => ['label' => 'Fechada', 'class' => 'bg-accent-100 text-accent-800 dark:bg-accent-900/30 dark:text-accent-300'],
        'past' => ['label' => 'Vencida', 'class' => 'bg-surface-100 text-surface-700 dark:bg-surface-700 dark:text-surface-300'],
    ];
    $outstanding = max(0, $invoice['total'] - $invoice['paid']);
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Navegação entre faturas --}}
    <div class="flex items-center justify-between bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 px-2 py-2">
        <a href="{{ route('wallets.invoice', ['wallet' => $wallet->id, 'ref' => $invoice['previous']]) }}" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-600 dark:text-surface-300" aria-label="Fatura anterior"><i class="fas fa-chevron-left"></i></a>
        <div class="text-center">
            <p class="font-semibold text-surface-900 dark:text-white">Fatura de {{ $invoice['label'] }}</p>
            @unless($invoice['is_current'])
                <a href="{{ route('wallets.invoice', $wallet) }}" class="text-xs text-primary-600 hover:underline">ir para a fatura atual</a>
            @endunless
        </div>
        <a href="{{ route('wallets.invoice', ['wallet' => $wallet->id, 'ref' => $invoice['next']]) }}" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-600 dark:text-surface-300" aria-label="Próxima fatura"><i class="fas fa-chevron-right"></i></a>
    </div>

    {{-- Resumo --}}
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: {{ $wallet->color }}20">
                    <i class="fas {{ $wallet->icon }} text-xl" style="color: {{ $wallet->color }}"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-surface-900 dark:text-white">{{ $wallet->name }}</h1>
                    <p class="text-sm text-surface-500">
                        Compras de {{ $invoice['cycle']['start']->format('d/m') }} a {{ $invoice['cycle']['end']->format('d/m/Y') }}
                    </p>
                </div>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $states[$invoice['state']]['class'] }}">
                {{ $states[$invoice['state']]['label'] }}
            </span>
        </div>

        <div class="grid sm:grid-cols-3 gap-4 mt-6">
            <div class="bg-surface-50 dark:bg-surface-800/60 rounded-xl p-4">
                <p class="text-sm text-surface-500">Total da fatura</p>
                <p class="num text-2xl font-semibold text-surface-900 dark:text-white">{{ $brl($invoice['total']) }}</p>
            </div>
            <div class="bg-surface-50 dark:bg-surface-800/60 rounded-xl p-4">
                <p class="text-sm text-surface-500">Fechamento / Vencimento</p>
                <p class="num text-lg font-medium text-surface-900 dark:text-white">{{ $invoice['cycle']['end']->format('d/m') }} · {{ $invoice['cycle']['due']->format('d/m/Y') }}</p>
            </div>
            <div class="bg-surface-50 dark:bg-surface-800/60 rounded-xl p-4">
                <p class="text-sm text-surface-500">Limite disponível</p>
                <p class="num text-lg font-medium text-surface-900 dark:text-white">
                    {{ $wallet->credit_limit ? $brl($wallet->available_credit) : 'Não informado' }}
                </p>
            </div>
        </div>

        {{-- Pagar fatura --}}
        @if($invoice['total'] > 0)
            <div class="mt-6 pt-6 border-t border-surface-100 dark:border-surface-800" x-data="{ open: false }">
                @if($invoice['paid'] > 0)
                    <p class="text-sm text-primary-700 dark:text-primary-400 mb-3">
                        <i class="fas fa-circle-check mr-1"></i> Pagamentos registrados neste ciclo: {{ $brl($invoice['paid']) }}
                    </p>
                @endif
                @if($sourceWallets->isEmpty())
                    <p class="text-sm text-surface-500">Cadastre uma conta corrente ou poupança para registrar o pagamento da fatura.</p>
                @else
                    <button type="button" @click="open = !open" class="btn-primary">
                        <i class="fas fa-money-check-dollar mr-2"></i> Registrar pagamento da fatura
                    </button>
                    <form autocomplete="off" x-show="open" x-cloak action="{{ route('wallets.invoice.pay', $wallet) }}" method="POST" class="mt-4 grid sm:grid-cols-4 gap-3 items-end">
                        @csrf
                        <input type="hidden" name="ref" value="{{ $invoice['reference'] }}">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-surface-500 mb-1">Pagar com</label>
                            <select autocomplete="off" name="source_wallet_id" required class="input">
                                @foreach($sourceWallets as $source)
                                    <option value="{{ $source->id }}">{{ $source->name }} (saldo {{ $brl($source->balance) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-surface-500 mb-1">Valor (R$)</label>
                            <input type="number" name="amount" step="0.01" min="0.01" required value="{{ number_format($outstanding ?: $invoice['total'], 2, '.', '') }}" class="input" autocomplete="off">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-surface-500 mb-1">Data</label>
                            <input type="date" name="date" required value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" autocomplete="off" class="input">
                        </div>
                        <p class="sm:col-span-3 text-xs text-surface-500">Gera uma saída na conta escolhida e uma entrada no cartão (abatendo a dívida). Pode ser um valor parcial.</p>
                        <button type="submit" class="btn-primary">Confirmar</button>
                        @if($errors->any())
                            <div class="sm:col-span-4 text-red-500 text-xs">{{ $errors->first() }}</div>
                        @endif
                    </form>
                @endif
            </div>
        @endif
    </div>

    {{-- Lançamentos --}}
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-surface-100 dark:border-surface-800 flex items-center justify-between">
            <h2 class="font-semibold text-surface-900 dark:text-white">Lançamentos ({{ $invoice['charges']->count() }})</h2>
            <a href="{{ route('transactions.create', ['wallet_id' => $wallet->id]) }}" class="text-sm text-primary-600 hover:underline"><i class="fas fa-plus mr-1"></i>Nova compra</a>
        </div>
        @forelse($invoice['charges']->concat($invoice['credits'])->sortBy('date') as $item)
            <a href="{{ route('transactions.show', $item) }}" class="px-6 py-3 flex items-center justify-between gap-3 border-b last:border-0 border-surface-100 dark:border-surface-800 hover:bg-surface-50 dark:hover:bg-surface-800/40">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-surface-900 dark:text-white truncate">
                        {{ $item->description }}
                        @if($item->isInstallment())<span class="text-xs text-primary-600 ml-1">{{ $item->installment_label }}</span>@endif
                    </p>
                    <p class="text-xs text-surface-500">{{ $item->date->format('d/m') }}{{ $item->category ? ' · ' . $item->category->name : '' }}</p>
                </div>
                <span class="num text-sm font-semibold whitespace-nowrap {{ $item->isIncome() ? 'text-primary-600 dark:text-primary-400' : 'text-surface-900 dark:text-white' }}">
                    {{ $item->isIncome() ? '− ' : '' }}{{ $brl($item->amount) }}
                </span>
            </a>
        @empty
            <p class="px-6 py-10 text-center text-sm text-surface-500">Nenhuma compra neste ciclo.</p>
        @endforelse
    </div>
</div>
@endsection
