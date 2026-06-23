@auth
@php
    $user = Auth::user();
    $subscription = $user->subscription;
@endphp

{{-- Subscription expired - needs renewal --}}
@if($subscription && $subscription->needsRenewal())
<div class="bg-gradient-to-r from-red-700 to-red-600 text-white px-4 py-3 text-center text-sm">
    <i class="fas fa-exclamation-triangle mr-1 text-yellow-300"></i>
    <strong>Sua assinatura venceu.</strong> As funcionalidades do Plano Profissional estão bloqueadas.
    <a href="{{ route('subscription.plans') }}" class="underline font-bold ml-1 hover:text-yellow-200">Renovar agora →</a>
    <span class="block sm:inline sm:ml-2 text-xs text-red-200">Renove com 40% OFF — apenas R$ 24,90/mês</span>
</div>

{{-- Free user near limits --}}
@elseif($user->isFree())
@php
    $limits = $user->plan_limits;
    $walletsCount = $user->wallets()->count();
    $transactionsCount = $user->transactions()->whereMonth('date', now()->month)->whereYear('date', now()->year)->count();
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
