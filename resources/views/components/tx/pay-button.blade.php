@props(['transaction', 'compact' => false])

{{-- Marca como paga/recebida (pendente) ou reabre (paga). --}}
@if($transaction->isPending())
    <form autocomplete="off" action="{{ route('transactions.pay', $transaction) }}" method="POST" class="inline">
        @csrf @method('PATCH')
        <button type="submit"
            class="{{ $compact ? 'p-1.5 rounded hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-emerald-600' : 'inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg transition' }}"
            title="{{ $transaction->isIncome() ? 'Confirmar recebimento' : 'Marcar como pago' }}">
            <i class="fas fa-check{{ $compact ? ' text-sm' : ' mr-1' }}"></i>
            @unless($compact) {{ $transaction->isIncome() ? 'Recebi' : 'Paguei' }} @endunless
        </button>
    </form>
@else
    <form autocomplete="off" action="{{ route('transactions.unpay', $transaction) }}" method="POST" class="inline"
        data-confirm="Reabrir como pendente? O valor sairá do saldo da carteira até ser pago novamente.">
        @csrf @method('PATCH')
        <button type="submit"
            class="{{ $compact ? 'p-1.5 rounded hover:bg-surface-100 dark:hover:bg-surface-700 text-surface-400' : 'inline-flex items-center px-3 py-1.5 border border-surface-300 dark:border-surface-600 text-surface-600 dark:text-surface-300 text-xs font-medium rounded-lg hover:bg-surface-50 dark:hover:bg-surface-700 transition' }}"
            title="Reabrir como pendente">
            <i class="fas fa-rotate-left{{ $compact ? ' text-sm' : ' mr-1' }}"></i>
            @unless($compact) Reabrir @endunless
        </button>
    </form>
@endif
