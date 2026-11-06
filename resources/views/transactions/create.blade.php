@extends('layouts.app')

@section('title', 'Nova Transação')

@section('breadcrumb')
<x-ui.breadcrumb :items="[
    ['label' => 'Transações', 'url' => route('transactions.index')],
    ['label' => 'Nova Transação'],
]" />
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="section-card">
        <div class="p-6 border-b border-surface-100 dark:border-surface-800">
            <h1 class="text-xl font-semibold text-surface-900 dark:text-white">
                <i class="fas fa-exchange-alt text-primary-500 mr-2"></i> Nova Transação
            </h1>
            <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">
                Registre uma receita ou despesa — já paga ou agendada (conta a pagar/receber), à vista ou parcelada.
            </p>
        </div>

        @if($wallets->isEmpty())
            <div class="p-6 text-center">
                <i class="fas fa-wallet text-4xl text-surface-300 dark:text-surface-600 mb-3"></i>
                <p class="text-surface-600 dark:text-surface-400 mb-4">Você precisa de pelo menos uma carteira para registrar transações.</p>
                <a href="{{ route('wallets.create') }}" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i> Criar carteira
                </a>
            </div>
        @else
            <form autocomplete="off" action="{{ route('transactions.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf

                @include('transactions.partials.form', ['transaction' => null])

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-100 dark:border-surface-800">
                    <a href="{{ route('transactions.index') }}" class="btn-ghost">Cancelar</a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save mr-1"></i> Registrar
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
