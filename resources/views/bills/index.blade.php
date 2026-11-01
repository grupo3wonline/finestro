@extends('layouts.app')

@section('title', 'Contas a pagar e receber')

@section('breadcrumb')
<x-ui.breadcrumb :items="[['label' => 'Contas']]" />
@endsection

@php
    $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $isIncome = $type === 'income';
    $sections = [
        'overdue' => ['title' => 'Atrasadas', 'icon' => 'fa-triangle-exclamation', 'color' => 'text-red-600', 'ring' => 'border-red-200 dark:border-red-900/50'],
        'today' => ['title' => 'Vencem hoje', 'icon' => 'fa-bell', 'color' => 'text-amber-600', 'ring' => 'border-amber-200 dark:border-amber-900/50'],
        'next7' => ['title' => 'Próximos 7 dias', 'icon' => 'fa-calendar-week', 'color' => 'text-primary-600', 'ring' => 'border-surface-200 dark:border-surface-700'],
        'later' => ['title' => 'Até 60 dias', 'icon' => 'fa-calendar', 'color' => 'text-surface-500', 'ring' => 'border-surface-200 dark:border-surface-700'],
    ];
    $totalOpen = $totals->sum();
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-surface-900 dark:text-white">Contas a {{ $isIncome ? 'receber' : 'pagar' }}</h1>
            <p class="text-surface-600 dark:text-surface-400 text-sm">Tudo que está pendente, organizado por vencimento. Marcar como {{ $isIncome ? 'recebida' : 'paga' }} atualiza o saldo da carteira.</p>
        </div>
        <a href="{{ route('transactions.create', ['type' => $type, 'status' => 'pending']) }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
            <i class="fas fa-calendar-plus mr-2"></i> Agendar {{ $isIncome ? 'recebimento' : 'conta' }}
        </a>
    </div>

    {{-- Abas --}}
    <div class="inline-flex p-1 bg-surface-100 dark:bg-surface-800 rounded-xl" role="tablist">
        <a href="{{ route('bills.index') }}" role="tab" aria-selected="{{ $isIncome ? 'false' : 'true' }}"
            class="px-4 py-2 rounded-lg text-sm font-medium transition {{ !$isIncome ? 'bg-white dark:bg-surface-700 shadow-sm text-surface-900 dark:text-white' : 'text-surface-500 hover:text-surface-700 dark:hover:text-surface-300' }}">
            <i class="fas fa-arrow-down text-red-500 mr-1"></i> A pagar
            <span class="ml-1 text-xs px-1.5 py-0.5 rounded-full bg-surface-200 dark:bg-surface-600">{{ $counts['expense'] }}</span>
        </a>
        <a href="{{ route('bills.index', ['tipo' => 'receber']) }}" role="tab" aria-selected="{{ $isIncome ? 'true' : 'false' }}"
            class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $isIncome ? 'bg-white dark:bg-surface-700 shadow-sm text-surface-900 dark:text-white' : 'text-surface-500 hover:text-surface-700 dark:hover:text-surface-300' }}">
            <i class="fas fa-arrow-up text-green-500 mr-1"></i> A receber
            <span class="ml-1 text-xs px-1.5 py-0.5 rounded-full bg-surface-200 dark:bg-surface-600">{{ $counts['income'] }}</span>
        </a>
    </div>

    {{-- Totais --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($sections as $key => $section)
            <div class="bg-white dark:bg-surface-800 rounded-xl p-4 shadow-sm border {{ $section['ring'] }}">
                <p class="text-xs font-medium uppercase {{ $section['color'] }}"><i class="fas {{ $section['icon'] }} mr-1"></i>{{ $section['title'] }}</p>
                <p class="text-xl font-bold text-surface-900 dark:text-white mt-1">{{ $brl($totals[$key]) }}</p>
                <p class="text-xs text-surface-500">{{ $groups[$key]->count() }} conta(s)</p>
            </div>
        @endforeach
    </div>

    @if($totalOpen == 0)
        <div class="bg-white dark:bg-surface-800 rounded-xl shadow-sm border border-surface-200 dark:border-surface-700 text-center py-16 px-4">
            <i class="fas fa-circle-check text-5xl text-emerald-400 mb-4"></i>
            <h3 class="text-lg font-medium text-surface-900 dark:text-white mb-2">Nenhuma conta {{ $isIncome ? 'a receber' : 'a pagar' }} nos próximos 60 dias</h3>
            <p class="text-surface-500 dark:text-surface-400 text-sm max-w-md mx-auto">
                Ao registrar uma transação com situação <strong>{{ $isIncome ? '"A receber"' : '"A pagar"' }}</strong>, ou uma compra parcelada no boleto/carnê, ela aparece aqui.
            </p>
        </div>
    @else
        @foreach($sections as $key => $section)
            @continue($groups[$key]->isEmpty())
            <section class="bg-white dark:bg-surface-800 rounded-xl shadow-sm border {{ $section['ring'] }} overflow-hidden">
                <header class="px-5 py-3 border-b border-surface-200 dark:border-surface-700 flex items-center justify-between">
                    <h2 class="font-semibold {{ $section['color'] }}"><i class="fas {{ $section['icon'] }} mr-2"></i>{{ $section['title'] }}</h2>
                    <span class="text-sm font-medium text-surface-700 dark:text-surface-300">{{ $brl($totals[$key]) }}</span>
                </header>
                <ul class="divide-y divide-surface-200 dark:divide-surface-700">
                    @foreach($groups[$key] as $bill)
                        <li class="px-5 py-3 flex flex-col sm:flex-row sm:items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('transactions.show', $bill) }}" class="font-medium text-surface-900 dark:text-white hover:text-primary-600">
                                    {{ $bill->description }}
                                    @if($bill->isInstallment())<span class="text-xs text-primary-600 ml-1">{{ $bill->installment_label }}</span>@endif
                                </a>
                                <p class="text-xs text-surface-500 dark:text-surface-400 mt-0.5">
                                    <i class="far fa-calendar mr-0.5"></i> {{ $bill->effective_due_date->format('d/m/Y') }}
                                    @if($key === 'overdue')
                                        <span class="text-red-600">({{ (int) $bill->effective_due_date->diffInDays(now()->startOfDay()) }} dia(s) de atraso)</span>
                                    @endif
                                    · {{ $bill->wallet->name ?? '-' }}
                                    @if($bill->category) · {{ $bill->category->name }} @endif
                                    @if($bill->payment_method_label) · {{ $bill->payment_method_label }} @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-3 sm:justify-end">
                                <span class="font-semibold whitespace-nowrap {{ $isIncome ? 'text-green-600' : 'text-red-600' }}">{{ $brl($bill->amount) }}</span>
                                <x-tx.pay-button :transaction="$bill" />
                                <a href="{{ route('transactions.edit', $bill) }}" class="p-1.5 rounded hover:bg-surface-100 dark:hover:bg-surface-700 text-surface-500" title="Editar">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    @endif
</div>
@endsection
