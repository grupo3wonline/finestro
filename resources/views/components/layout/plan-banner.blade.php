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

{{-- Professional period ends (renewal or scheduled cancellation) within the warning window (7 days) --}}
@elseif($subscription && $subscription->isRenewalDueSoon())
@php
    $renewalDays = $subscription->daysUntilPeriodEnd() ?? 0;
    $renewalWhen = $renewalDays === 0 ? 'hoje' : ($renewalDays === 1 ? 'em 1 dia' : "em {$renewalDays} dias");
    $renewalDate = $subscription->current_period_end?->format('d/m/Y');
@endphp
<div x-data="{ open: true }" x-show="open" x-cloak role="status"
    class="bg-surface-900 dark:bg-surface-800 text-white px-4 py-2.5 text-sm">
    <div class="max-w-7xl mx-auto flex items-center justify-center gap-3 text-center">
        <p>
            <i class="fas fa-calendar-check mr-1 text-accent-300" aria-hidden="true"></i>
            @if($subscription->cancel_at_period_end)
                <strong>Sua assinatura Profissional termina {{ $renewalWhen }}</strong> ({{ $renewalDate }}). Retome para manter carteiras, relatórios e IA sem limites.
                <a href="{{ route('subscription.index') }}" class="underline font-bold ml-1 text-accent-300 hover:text-accent-200">Retomar assinatura →</a>
            @else
                <strong>Sua assinatura Profissional renova {{ $renewalWhen }}</strong> ({{ $renewalDate }}). Confira seus dados de pagamento para não perder os recursos.
                <a href="{{ route('subscription.index') }}" class="underline font-bold ml-1 text-accent-300 hover:text-accent-200">Gerenciar assinatura →</a>
            @endif
        </p>
        <button type="button" @click="open = false" class="shrink-0 p-1 text-surface-300 hover:text-white" aria-label="Fechar lembrete">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
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
