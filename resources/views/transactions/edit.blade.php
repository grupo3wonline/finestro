@extends('layouts.app')

@section('title', 'Editar Transação')

@section('breadcrumb')
<x-ui.breadcrumb :items="[
    ['label' => 'Transações', 'url' => route('transactions.index')],
    ['label' => 'Editar'],
]" />
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="section-card">
        <div class="p-6 border-b border-surface-100 dark:border-surface-800">
            <h1 class="text-xl font-semibold text-surface-900 dark:text-white">
                <i class="fas fa-edit text-primary-500 mr-2"></i> Editar Transação
            </h1>
            @if($transaction->isInstallment())
                <p class="text-sm text-accent-700 dark:text-accent-400 mt-2">
                    <i class="fas fa-layer-group mr-1"></i>
                    Parcela {{ $transaction->installment_label }} — a alteração vale somente para esta parcela.
                </p>
            @endif
        </div>

        <form autocomplete="off" action="{{ route('transactions.update', $transaction) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf @method('PUT')

            @include('transactions.partials.form')

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-100 dark:border-surface-800">
                <a href="{{ route('transactions.show', $transaction) }}" class="btn-ghost">Cancelar</a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-1"></i> Salvar alterações
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
