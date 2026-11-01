@props(['transaction', 'compact' => false])

{{-- Marca como paga/recebida (pendente) ou reabre (paga). --}}
@if($transaction->isPending())
    <form action="{{ route('transactions.pay', $transaction) }}" method="POST" class="inline">
        @csrf @method('PATCH')
        <button type="submit"
            class="{{ $compact ? 'p-1.5 rounded hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-emerald-600' : 'inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg transition' }}"
            title="{{ $transaction->isIncome() ? 'Confirmar recebimento' : 'Marcar como pago' }}">
            <i class="fas fa-check{{ $compact ? ' text-sm' : ' mr-1' }}"></i>
            @unless($compact) {{ $transaction->isIncome() ? 'Recebi' : 'Paguei' }} @endunless
        </button>
    </form>
@else
    <form action="{{ route('transactions.unpay', $transaction) }}" method="POST" class="inline"
        onsubmit="return confirm('Reabrir como pendente? O valor sairá do saldo da carteira até ser pago novamente.')">
        @csrf @method('PATCH')
        <button type="submit"
            class="{{ $compact ? 'p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400' : 'inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition' }}"
            title="Reabrir como pendente">
            <i class="fas fa-rotate-left{{ $compact ? ' text-sm' : ' mr-1' }}"></i>
            @unless($compact) Reabrir @endunless
        </button>
    </form>
@endif
