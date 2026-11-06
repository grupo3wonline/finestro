@extends('layouts.app')

@section('title', 'Detalhes da Transação')

@section('breadcrumb')
<x-ui.breadcrumb :items="[
    ['label' => 'Transações', 'url' => route('transactions.index', ['month' => $transaction->date->month, 'year' => $transaction->date->year])],
    ['label' => 'Detalhes'],
]" />
@endsection

@php
    $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $card = 'bg-surface-50 dark:bg-surface-800/60 rounded-xl p-4';
    $formatChange = function ($field, $value) {
        if (is_null($value) || $value === '') return '—';
        if (is_bool($value)) return $value ? 'Sim' : 'Não';
        return match ($field) {
            'amount' => 'R$ ' . number_format((float) $value, 2, ',', '.'),
            'date', 'due_date' => \Carbon\Carbon::parse($value)->format('d/m/Y'),
            'type' => $value === 'income' ? 'Receita' : 'Despesa',
            'status' => $value === 'paid' ? 'Pago' : 'Pendente',
            'recurring_frequency' => ['daily' => 'Diária', 'weekly' => 'Semanal', 'monthly' => 'Mensal', 'yearly' => 'Anual'][$value] ?? $value,
            default => (string) $value,
        };
    };
@endphp

@section('content')
<div class="max-w-4xl mx-auto grid lg:grid-cols-3 gap-6">
    {{-- Detalhes --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-6">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-surface-900 dark:text-white">{{ $transaction->description }}</h1>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $transaction->isIncome() ? 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                            {{ $transaction->isIncome() ? 'Receita' : 'Despesa' }}
                        </span>
                        <x-tx.status-badge :transaction="$transaction" />
                        @if($transaction->isInstallment())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                                <i class="fas fa-layer-group mr-1"></i> Parcela {{ $transaction->installment_label }}
                            </span>
                        @endif
                        @if($transaction->is_recurring)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-surface-100 text-surface-700 dark:bg-surface-700 dark:text-surface-300">
                                <i class="fas fa-rotate mr-1"></i> Recorrente
                            </span>
                        @endif
                    </div>
                </div>
                <x-tx.pay-button :transaction="$transaction" />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="{{ $card }}">
                    <p class="text-sm text-surface-500 dark:text-surface-400">Valor</p>
                    <p class="num text-2xl font-semibold {{ $transaction->isIncome() ? 'text-primary-600 dark:text-primary-400' : 'text-red-600' }}">
                        {{ $transaction->isIncome() ? '+' : '-' }} {{ $brl($transaction->amount) }}
                    </p>
                </div>
                <div class="{{ $card }}">
                    <p class="text-sm text-surface-500 dark:text-surface-400">Data / Vencimento</p>
                    <p class="num text-lg font-medium text-surface-900 dark:text-white">
                        {{ $transaction->date->format('d/m/Y') }}
                        @if($transaction->due_date && !$transaction->due_date->equalTo($transaction->date))
                            <span class="text-sm text-surface-500">· vence {{ $transaction->due_date->format('d/m/Y') }}</span>
                        @endif
                    </p>
                    @if($transaction->isPaid() && $transaction->paid_at)
                        <p class="text-xs text-surface-500 mt-1">{{ $transaction->isIncome() ? 'Recebido' : 'Pago' }} em {{ $transaction->paid_at->format('d/m/Y') }}</p>
                    @endif
                </div>
                <div class="{{ $card }}">
                    <p class="text-sm text-surface-500 dark:text-surface-400">Carteira</p>
                    <p class="text-lg font-medium text-surface-900 dark:text-white">
                        @if($transaction->wallet)
                            <a href="{{ route('wallets.show', $transaction->wallet) }}" class="hover:text-primary-600">{{ $transaction->wallet->name }}</a>
                        @else
                            -
                        @endif
                    </p>
                </div>
                <div class="{{ $card }}">
                    <p class="text-sm text-surface-500 dark:text-surface-400">Categoria</p>
                    <p class="text-lg font-medium text-surface-900 dark:text-white">{{ $transaction->category->name ?? 'Sem categoria' }}</p>
                </div>
                <div class="{{ $card }}">
                    <p class="text-sm text-surface-500 dark:text-surface-400">Forma de pagamento</p>
                    <p class="text-lg font-medium text-surface-900 dark:text-white">{{ $transaction->payment_method_label ?? 'Não informada' }}</p>
                </div>
                <div class="{{ $card }}">
                    <p class="text-sm text-surface-500 dark:text-surface-400">Comprovante</p>
                    @if($transaction->hasReceipt())
                        <a href="{{ route('transactions.receipt', $transaction) }}" target="_blank" rel="noopener" class="inline-flex items-center text-lg font-medium text-primary-600 hover:underline">
                            <i class="fas fa-paperclip mr-2"></i> Ver comprovante
                        </a>
                    @else
                        <p class="text-lg font-medium text-surface-400">Nenhum</p>
                    @endif
                </div>
            </div>

            @if($transaction->notes)
                <div class="mt-4 {{ $card }}">
                    <p class="text-sm text-surface-500 dark:text-surface-400 mb-1">Observações</p>
                    <p class="text-surface-700 dark:text-surface-300 whitespace-pre-line">{{ $transaction->notes }}</p>
                </div>
            @endif

            {{-- Ações --}}
            <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-surface-100 dark:border-surface-800" x-data="{ confirmDelete: false }">
                <a href="{{ route('transactions.edit', $transaction) }}" class="btn-primary">
                    <i class="fas fa-edit mr-1"></i> Editar
                </a>
                <button type="button" @click="confirmDelete = !confirmDelete" class="px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition">
                    <i class="fas fa-trash mr-1"></i> Excluir
                </button>
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('transactions.index') }}" class="btn-ghost">Voltar</a>

                <form autocomplete="off" x-show="confirmDelete" x-cloak action="{{ route('transactions.destroy', $transaction) }}" method="POST"
                    class="w-full mt-2 p-4 rounded-xl border border-red-200 dark:border-red-900/50 bg-red-50/60 dark:bg-red-900/10 space-y-3">
                    @csrf @method('DELETE')
                    <label for="reason" class="block text-sm font-medium text-surface-700 dark:text-surface-300">Motivo da exclusão <span class="text-xs font-normal text-surface-500">(opcional — fica registrado no histórico)</span></label>
                    <input type="text" name="reason" id="reason" maxlength="255" placeholder="Ex.: lançamento duplicado" class="input text-sm" autocomplete="off">
                    @if($transaction->isInstallment())
                        <div class="flex flex-col sm:flex-row gap-3 text-sm">
                            <label class="inline-flex items-center gap-2"><input type="radio" name="scope" value="single" checked> Só esta parcela</label>
                            <label class="inline-flex items-center gap-2"><input type="radio" name="scope" value="installments"> Todas as {{ $transaction->installment_total }} parcelas</label>
                        </div>
                    @endif
                    <button type="submit" class="btn-danger">
                        Confirmar exclusão
                    </button>
                    <p class="text-xs text-surface-500">A transação vai para a lixeira e pode ser restaurada. O saldo da carteira é ajustado.</p>
                </form>
            </div>
        </div>

        {{-- Parcelas --}}
        @if($transaction->isInstallment())
            <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-surface-100 dark:border-surface-800 flex items-center justify-between">
                    <h2 class="font-semibold text-surface-900 dark:text-white"><i class="fas fa-layer-group text-primary-500 mr-2"></i>Parcelamento</h2>
                    <span class="num text-sm text-surface-500">Total {{ $brl($transaction->installments->sum('amount')) }}</span>
                </div>
                <ul class="divide-y divide-surface-100 dark:divide-surface-800">
                    @foreach($transaction->installments as $installment)
                        <li class="px-6 py-3 flex items-center justify-between gap-3 {{ $installment->id === $transaction->id ? 'bg-primary-50/50 dark:bg-primary-900/10' : '' }}">
                            <a href="{{ route('transactions.show', $installment) }}" class="text-sm text-surface-700 dark:text-surface-300 hover:text-primary-600">
                                <span class="font-medium">{{ $installment->installment_label }}</span>
                                <span class="num text-surface-500 ml-2">{{ $installment->date->format('d/m/Y') }}</span>
                            </a>
                            <div class="flex items-center gap-3">
                                <x-tx.status-badge :transaction="$installment" />
                                <span class="num text-sm font-medium text-surface-900 dark:text-white w-28 text-right">{{ $brl($installment->amount) }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Histórico (auditoria) --}}
    <aside class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-6 h-fit">
        <h2 class="font-semibold text-surface-900 dark:text-white mb-4"><i class="fas fa-clock-rotate-left text-primary-500 mr-2"></i>Histórico</h2>

        @forelse($transaction->histories as $history)
            <div class="relative pl-6 pb-5 last:pb-0 border-l border-surface-200 dark:border-surface-700 last:border-transparent">
                <span class="absolute -left-[9px] top-0 w-[18px] h-[18px] rounded-full bg-white dark:bg-surface-900 border border-surface-200 dark:border-surface-600 flex items-center justify-center">
                    <i class="fas {{ $history->action_icon }} text-[9px]"></i>
                </span>
                <p class="text-sm font-medium text-surface-900 dark:text-white">{{ $history->action_label }}</p>
                <p class="text-xs text-surface-500">{{ $history->created_at->format('d/m/Y H:i') }}{{ $history->user ? ' · ' . $history->user->name : '' }}</p>

                @if($history->changes && $history->action === 'updated')
                    <ul class="mt-2 space-y-1">
                        @foreach($history->changes as $field => $change)
                            <li class="text-xs text-surface-600 dark:text-surface-400">
                                <span class="font-medium">{{ \App\Models\TransactionHistory::FIELD_LABELS[$field] ?? $field }}:</span>
                                <span class="line-through opacity-70">{{ $formatChange($field, $change['old'] ?? null) }}</span>
                                <i class="fas fa-arrow-right text-[9px] mx-0.5"></i>
                                {{ $formatChange($field, $change['new'] ?? null) }}
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if($history->reason)
                    <p class="mt-2 text-xs italic text-surface-600 dark:text-surface-400 bg-surface-50 dark:bg-surface-800/60 rounded px-2 py-1">"{{ $history->reason }}"</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-surface-500">Sem registros. O histórico passa a ser gravado a partir desta versão do Finestro.</p>
        @endforelse
    </aside>
</div>
@endsection
