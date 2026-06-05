@auth
@if(Auth::user()->isFree())
@php
    $limits = Auth::user()->plan_limits;
    $walletsCount = Auth::user()->wallets()->count();
    $transactionsCount = Auth::user()->transactions()->whereMonth('date', now()->month)->whereYear('date', now()->year)->count();
    $nearLimit = ($walletsCount >= $limits['wallets']) || ($transactionsCount >= ($limits['transactions_per_month'] * 0.8));
@endphp
@if($nearLimit)
<div class="bg-gradient-to-r from-red-600 to-orange-500 text-white px-4 py-2 text-center text-sm">
    <i class="fas fa-fire mr-1 text-yellow-300"></i>
    <strong>OFERTA LIMITADA:</strong> Plano Profissional com <strong>40% OFF</strong> — de <span class="line-through">R$ 41,58</span> por <strong>R$ 24,90/mês</strong>.
    <a href="{{ route('subscription.plans') }}" class="underline font-bold ml-1 hover:text-yellow-200">Garantir agora →</a>
</div>
@endif
@endif
@endauth
