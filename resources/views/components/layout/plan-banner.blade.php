@auth
@if(Auth::user()->isFree())
@php
    $limits = Auth::user()->plan_limits;
    $walletsCount = Auth::user()->wallets()->count();
    $transactionsCount = Auth::user()->transactions()->whereMonth('date', now()->month)->whereYear('date', now()->year)->count();
    $nearLimit = ($walletsCount >= $limits['wallets']) || ($transactionsCount >= ($limits['transactions_per_month'] * 0.8));
@endphp
@if($nearLimit)
<div class="bg-gradient-to-r from-primary-600 to-primary-800 text-white px-4 py-2 text-center text-sm">
    <i class="fas fa-info-circle mr-1"></i>
    Você está próximo do limite do plano gratuito.
    <a href="{{ route('subscription.plans') }}" class="underline font-semibold ml-1 hover:text-primary-200">Fazer upgrade →</a>
</div>
@endif
@endif
@endauth
