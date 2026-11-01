@auth
@inject('pricing', \App\Interfaces\Services\PlanPricingInterface::class)
@php
    $user = Auth::user();
    $subscription = $user->subscription;
@endphp

{{-- Renewal payment failed - access fell back to Free --}}
@if($subscription && $subscription->status === \App\Enums\SubscriptionStatus::PAST_DUE)
<div class="bg-gradient-to-r from-red-700 to-red-600 text-white px-4 py-3 text-center text-sm">
    <i class="fas fa-triangle-exclamation mr-1 text-accent-300"></i>
    <strong>Pagamento pendente.</strong> Não conseguimos confirmar a última cobrança e os recursos do Plano Profissional estão bloqueados.
    <a href="{{ route('subscription.index') }}" class="underline font-bold ml-1 hover:text-accent-200">Ver Financeiro →</a>
</div>

{{-- Subscription expired - needs renewal --}}
@elseif($subscription && $subscription->needsRenewal())
<div class="bg-gradient-to-r from-red-700 to-red-600 text-white px-4 py-3 text-center text-sm">
    <i class="fas fa-exclamation-triangle mr-1 text-accent-300"></i>
    <strong>Sua assinatura venceu.</strong> As funcionalidades do Plano Profissional estão bloqueadas.
    <a href="{{ route('subscription.plans') }}" class="underline font-bold ml-1 hover:text-accent-200">Renovar agora →</a>
    <span class="block sm:inline sm:ml-2 text-xs text-red-100">Renove por {{ $pricing->formattedCurrent() }}/mês e recupere todos os recursos.</span>
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
<div class="bg-gradient-to-r from-primary-700 to-primary-600 text-white px-4 py-2 text-center text-sm">
    <i class="fas fa-gauge-high mr-1 text-accent-300"></i>
    <strong>Você está perto do limite do plano Básico.</strong> O Profissional libera carteiras e transações ilimitadas por <strong>{{ $pricing->formattedCurrent() }}/mês</strong>.
    <a href="{{ route('subscription.plans') }}" class="underline font-bold ml-1 hover:text-accent-200">Ver planos →</a>
</div>
@endif
@endif
@endauth
