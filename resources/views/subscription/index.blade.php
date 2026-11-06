@extends('layouts.app')

@section('title', 'Financeiro')

@section('breadcrumb')
    <x-ui.breadcrumb :items="[['label' => 'Financeiro']]" />
@endsection

@inject('pricing', \App\Interfaces\Services\PlanPricingInterface::class)

@php
    $isPro = $user->isProfessional();
    $hasPeriod = $subscription && $subscription->current_period_end;
    $pendingCancellation = $subscription?->hasPendingCancellation() ?? false;
    $periodEnd = $hasPeriod ? $subscription->current_period_end->format('d/m/Y') : null;

    $statusVariant = match (true) {
        ! $subscription => 'neutral',
        $subscription->isExpired() => 'danger',
        $pendingCancellation => 'warning',
        $subscription->status === \App\Enums\SubscriptionStatus::ACTIVE => 'success',
        $subscription->status === \App\Enums\SubscriptionStatus::TRIALING => 'info',
        $subscription->status === \App\Enums\SubscriptionStatus::PAST_DUE => 'warning',
        $subscription->status === \App\Enums\SubscriptionStatus::CANCELED => 'danger',
        default => 'neutral',
    };

    $paymentVariants = ['paid' => 'success', 'failed' => 'danger', 'pending' => 'warning', 'refunded' => 'neutral'];
@endphp

@section('content')
<div class="space-y-6">
    <x-ui.page-header title="Financeiro" subtitle="Seu plano, a vigência da assinatura e o histórico de pagamentos em um só lugar." icon="fa-receipt">
        <x-slot:actions>
            <x-ui.button :href="route('subscription.plans')" variant="outline" size="sm" icon="fa-layer-group" title="Compare os recursos dos planos Básico e Profissional">
                Ver planos
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if($subscription && $subscription->status === \App\Enums\SubscriptionStatus::PAST_DUE)
        <x-ui.alert variant="warning" title="Pagamento pendente">
            Não conseguimos confirmar o último pagamento da sua assinatura. O Stripe fará novas tentativas automaticamente;
            enquanto isso, sua conta usa os limites do plano Básico.
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Current plan --}}
        <section class="lg:col-span-1 bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 overflow-hidden" aria-labelledby="current-plan-title">
            <div class="p-6 {{ $isPro ? 'bg-gradient-to-br from-primary-600 to-primary-800 text-white' : 'bg-surface-50 dark:bg-surface-800/60' }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs uppercase tracking-wide {{ $isPro ? 'text-primary-100' : 'text-surface-500 dark:text-surface-400' }}">Plano atual</p>
                        <h2 id="current-plan-title" class="mt-1 text-2xl font-bold {{ $isPro ? 'text-white' : 'text-surface-900 dark:text-white' }}">
                            {{ $isPro ? $pricing->planName() : 'Básico' }}
                        </h2>
                        <p class="mt-2 num text-lg font-semibold {{ $isPro ? 'text-white' : 'text-surface-700 dark:text-surface-200' }}">
                            @if($isPro)
                                {{ $pricing->format($chargedPrice) }}<span class="text-sm font-normal opacity-80">/mês</span>
                            @else
                                Grátis
                            @endif
                        </p>
                    </div>
                    <span class="icon-box {{ $isPro ? 'bg-white/15 text-accent-300' : 'bg-surface-200 dark:bg-surface-700 text-surface-600 dark:text-surface-300' }}">
                        <i class="fas {{ $isPro ? 'fa-crown' : 'fa-user' }}"></i>
                    </span>
                </div>
            </div>
            <div class="p-6 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm text-surface-500 dark:text-surface-400">Status</span>
                    @if($subscription)
                        <x-ui.badge :variant="$statusVariant">{{ $subscription->status_label }}</x-ui.badge>
                    @else
                        <x-ui.badge variant="neutral">Sem assinatura</x-ui.badge>
                    @endif
                </div>
                <p class="text-xs text-surface-500 dark:text-surface-400">
                    @if($isPro)
                        Todos os recursos do plano Profissional estão liberados na sua conta.
                    @else
                        No plano Básico você tem 1 carteira, 100 transações por mês e as calculadoras gratuitas.
                    @endif
                </p>
            </div>
        </section>

        {{-- Validity + countdown --}}
        <section class="lg:col-span-2 bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-6" aria-labelledby="validity-title">
            <div class="flex items-center justify-between gap-3 mb-4">
                <h2 id="validity-title" class="text-lg font-semibold text-surface-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-calendar-check text-primary-500"></i> Vigência da assinatura
                </h2>
                <span class="text-surface-400 dark:text-surface-500 cursor-help" title="A vigência é o período já pago. A renovação acontece automaticamente no último dia, a menos que o cancelamento esteja agendado.">
                    <i class="fas fa-circle-question" aria-hidden="true"></i>
                    <span class="sr-only">Ajuda sobre vigência</span>
                </span>
            </div>

            @if($hasPeriod)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl bg-surface-50 dark:bg-surface-800/60">
                        <p class="text-xs text-surface-500 dark:text-surface-400">Início do período</p>
                        <p class="num text-lg font-semibold text-surface-900 dark:text-white">{{ $subscription->current_period_start?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div class="p-4 rounded-xl bg-surface-50 dark:bg-surface-800/60">
                        <p class="text-xs text-surface-500 dark:text-surface-400">{{ $subscription->cancel_at_period_end ? 'Acesso Profissional até' : 'Próxima renovação' }}</p>
                        <p class="num text-lg font-semibold text-surface-900 dark:text-white">{{ $periodEnd }}</p>
                    </div>
                </div>

                <div class="mt-4 p-4 rounded-xl border border-primary-100 dark:border-primary-900/40 bg-primary-50/60 dark:bg-primary-900/10"
                    x-data="billingCountdown('{{ $subscription->current_period_end->toIso8601String() }}', {{ $subscription->cancel_at_period_end ? 'true' : 'false' }})"
                    data-countdown-target="{{ $subscription->current_period_end->toIso8601String() }}">
                        <div x-show="!expired" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="text-center min-w-[3.5rem]"><p class="num text-2xl font-bold text-primary-700 dark:text-primary-300" x-text="days">{{ (int) $subscription->days_remaining }}</p><p class="text-[11px] text-surface-500 dark:text-surface-400">dias</p></div>
                                <div class="text-center min-w-[3.5rem]"><p class="num text-2xl font-bold text-primary-700 dark:text-primary-300" x-text="hours">00</p><p class="text-[11px] text-surface-500 dark:text-surface-400">horas</p></div>
                                <div class="text-center min-w-[3.5rem]"><p class="num text-2xl font-bold text-primary-700 dark:text-primary-300" x-text="minutes">00</p><p class="text-[11px] text-surface-500 dark:text-surface-400">min</p></div>
                                <div class="text-center min-w-[3.5rem]"><p class="num text-2xl font-bold text-primary-700 dark:text-primary-300" x-text="seconds">00</p><p class="text-[11px] text-surface-500 dark:text-surface-400">seg</p></div>
                            </div>
                            <p class="text-sm font-medium text-surface-700 dark:text-surface-300" x-text="label">
                                {{ $subscription->cancel_at_period_end ? 'até o fim do acesso Profissional' : 'até a renovação' }}
                            </p>
                        </div>
                        <p x-show="expired" x-cloak class="text-sm font-semibold text-red-600 dark:text-red-400"><i class="fas fa-circle-exclamation mr-1"></i> Vencido em {{ $periodEnd }}</p>
                </div>
            @else
                <x-ui.empty-state icon="fa-calendar" title="Nenhuma assinatura ativa" text="Ao assinar o plano Profissional, as datas de vigência e a contagem até a renovação aparecem aqui." class="py-8" />
            @endif
        </section>
    </div>

    {{-- Plan management --}}
    <x-ui.section-card title="Gestão do plano" icon="fa-sliders">
        @if($pendingCancellation)
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="font-semibold text-surface-900 dark:text-white flex items-center gap-2">
                        <x-ui.badge variant="warning" icon="fa-clock">Cancelamento agendado</x-ui.badge>
                    </p>
                    <p class="mt-2 text-sm text-surface-600 dark:text-surface-300">
                        Você mantém o plano Profissional até <strong>{{ $periodEnd ?? 'o fim do período atual' }}</strong>. Depois disso, sua conta volta ao plano Básico e nenhuma nova cobrança é feita.
                    </p>
                </div>
                <form autocomplete="off" action="{{ route('subscription.resume') }}" method="POST" class="shrink-0">
                    @csrf
                    <x-ui.button type="submit" variant="primary" icon="fa-rotate-left" title="Desfaz o cancelamento e mantém a renovação automática">
                        Manter meu plano Profissional
                    </x-ui.button>
                </form>
            </div>
        @elseif($isPro)
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="font-semibold text-surface-900 dark:text-white">Renovação automática ativa</p>
                    <p class="mt-1 text-sm text-surface-600 dark:text-surface-300">
                        Sua assinatura renova {{ $periodEnd ? 'em '.$periodEnd : 'ao fim do período atual' }} por {{ $pricing->format($chargedPrice) }}/mês.
                        Se cancelar, o acesso Profissional continua até essa data.
                    </p>
                </div>
                <form autocomplete="off" action="{{ route('subscription.cancel') }}" method="POST" class="shrink-0"
                    data-confirm="Seu plano Profissional continua ativo até {{ $periodEnd ?? 'o fim do período atual' }}. Depois disso sua conta volta ao plano Básico. Deseja agendar o cancelamento?">
                    @csrf
                    <x-ui.button type="submit" variant="outline" icon="fa-calendar-xmark" class="text-red-600 dark:text-red-400" title="Agenda o cancelamento para o fim do período pago">
                        Cancelar assinatura
                    </x-ui.button>
                </form>
            </div>
        @elseif($subscription && $subscription->stripe_subscription_id && $subscription->isPaymentPending())
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="font-semibold text-surface-900 dark:text-white">Pagamento pendente</p>
                    <p class="mt-1 text-sm text-surface-600 dark:text-surface-300">
                        O Stripe continua tentando cobrar a fatura em aberto. Se não quiser continuar, cancele agora: como não há período pago a manter, o cancelamento é imediato e sua conta segue no plano Básico.
                    </p>
                </div>
                <form autocomplete="off" action="{{ route('subscription.cancel') }}" method="POST" class="shrink-0"
                    data-confirm="A assinatura será cancelada imediatamente e o Stripe deixará de tentar a cobrança pendente. Sua conta continua no plano Básico. Deseja cancelar?">
                    @csrf
                    <x-ui.button type="submit" variant="outline" icon="fa-ban" class="text-red-600 dark:text-red-400" title="Cancela agora e interrompe novas tentativas de cobrança">
                        Cancelar assinatura
                    </x-ui.button>
                </form>
            </div>
        @else
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="font-semibold text-surface-900 dark:text-white">Libere carteiras, transações e metas ilimitadas</p>
                    <p class="mt-1 text-sm text-surface-600 dark:text-surface-300">
                        O Profissional inclui simuladores, relatórios exportáveis e análises com IA. Pagamento seguro via Stripe, cancele quando quiser.
                    </p>
                    <x-billing.promo-countdown class="mt-3" />
                </div>
                <form autocomplete="off" action="{{ route('subscription.checkout') }}" method="POST" class="shrink-0">
                    @csrf
                    <x-ui.button type="submit" variant="primary" icon="fa-crown" loading="Redirecionando para o pagamento...">
                        Assinar Profissional — {{ $pricing->formattedCurrent() }}/mês
                    </x-ui.button>
                </form>
            </div>
        @endif
    </x-ui.section-card>

    {{-- Plan usage --}}
    <x-ui.section-card title="Uso do Plano" icon="fa-chart-bar">
        @php
            $limits = $user->plan_limits;
            $walletsCount = $user->wallets()->count();
            $goalsCount = $user->goals()->count();
            $transactionsCount = $user->transactions()->whereMonth('date', now()->month)->whereYear('date', now()->year)->count();
            $categoriesCount = \App\Models\Category::where('user_id', $user->id)->count();
            $usage = [
                ['label' => 'Carteiras', 'icon' => 'fa-wallet', 'count' => $walletsCount, 'limit' => $limits['wallets'], 'bar' => 'bg-primary-500'],
                ['label' => 'Transações/mês', 'icon' => 'fa-exchange-alt', 'count' => $transactionsCount, 'limit' => $limits['transactions_per_month'], 'bar' => 'bg-secondary-500'],
                ['label' => 'Metas', 'icon' => 'fa-bullseye', 'count' => $goalsCount, 'limit' => $limits['goals'], 'bar' => 'bg-primary-500'],
                ['label' => 'Categorias', 'icon' => 'fa-tags', 'count' => $categoriesCount, 'limit' => $limits['categories'], 'bar' => 'bg-accent-500'],
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($usage as $item)
                <div class="p-4 bg-surface-50 dark:bg-surface-800/60 rounded-xl">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-surface-700 dark:text-surface-300"><i class="fas {{ $item['icon'] }} mr-1"></i> {{ $item['label'] }}</span>
                        <span class="text-xs text-surface-500 dark:text-surface-400">{{ $item['count'] }}/{{ $item['limit'] === PHP_INT_MAX ? '∞' : $item['limit'] }}</span>
                    </div>
                    @if($item['limit'] !== PHP_INT_MAX)
                        <div class="w-full bg-surface-200 dark:bg-surface-700 rounded-full h-2">
                            <div class="{{ $item['bar'] }} h-2 rounded-full" style="width: {{ min(100, ($item['count'] / $item['limit']) * 100) }}%"></div>
                        </div>
                    @else
                        <p class="text-xs text-primary-600 dark:text-primary-400">Ilimitado</p>
                    @endif
                </div>
            @endforeach
        </div>
    </x-ui.section-card>

    {{-- Payment history --}}
    <x-ui.section-card title="Histórico de pagamentos" icon="fa-clock-rotate-left" iconColor="secondary" bodyClass="p-0">
        @if($payments->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-50 dark:bg-surface-800/60">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase whitespace-nowrap">Data</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase whitespace-nowrap">Descrição</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-surface-500 dark:text-surface-400 uppercase whitespace-nowrap">Valor</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase whitespace-nowrap">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase whitespace-nowrap">Recibo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                        @foreach($payments as $payment)
                            <tr class="hover:bg-surface-50 dark:hover:bg-surface-800/30">
                                <td class="px-6 py-4 text-surface-600 dark:text-surface-400 whitespace-nowrap">
                                    {{ ($payment->paid_at ?? $payment->created_at)->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-surface-900 dark:text-white min-w-[12rem]">{{ $payment->description }}</td>
                                <td class="px-6 py-4 text-right num font-semibold text-surface-900 dark:text-white whitespace-nowrap">
                                    R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-ui.badge :variant="$paymentVariants[$payment->status] ?? 'neutral'">{{ $payment->status_label }}</x-ui.badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($payment->receipt_url)
                                        <a href="{{ $payment->receipt_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-primary-600 dark:text-primary-400 hover:underline font-medium" title="Abre o recibo emitido pelo Stripe em uma nova aba">
                                            <i class="fas fa-file-invoice"></i> Ver recibo
                                        </a>
                                    @else
                                        <span class="text-surface-400 dark:text-surface-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())
                <div class="px-6 py-4 border-t border-surface-100 dark:border-surface-800">
                    {{ $payments->links() }}
                </div>
            @endif
        @else
            <x-ui.empty-state icon="fa-receipt" title="Nenhum pagamento registrado" text="Cada cobrança da assinatura aparece aqui com valor, status e link para o recibo." />
        @endif
    </x-ui.section-card>

    <x-ui.tip icon="fa-circle-info">
        A assinatura renova automaticamente no fim de cada período. Ao cancelar, você continua com o plano Profissional até a data de vigência
        e não recebe novas cobranças. Mudou de ideia antes dessa data? Use "Manter meu plano Profissional" para desfazer o cancelamento.
    </x-ui.tip>
</div>

@push('scripts')
<script>
/**
 * Live countdown to the subscription period end (renewal or end of access).
 */
function billingCountdown(endIso, cancelAtPeriodEnd) {
    return {
        days: '0', hours: '00', minutes: '00', seconds: '00',
        expired: false,
        label: cancelAtPeriodEnd ? 'até o fim do acesso Profissional' : 'até a renovação',
        timer: null,
        init() {
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },
        destroy() {
            clearInterval(this.timer);
        },
        tick() {
            const diff = new Date(endIso).getTime() - Date.now();
            if (Number.isNaN(diff) || diff <= 0) {
                this.expired = true;
                clearInterval(this.timer);
                return;
            }
            const pad = (n) => String(n).padStart(2, '0');
            this.days = String(Math.floor(diff / 86400000));
            this.hours = pad(Math.floor((diff % 86400000) / 3600000));
            this.minutes = pad(Math.floor((diff % 3600000) / 60000));
            this.seconds = pad(Math.floor((diff % 60000) / 1000));
        },
    };
}
</script>
@endpush
@endsection
