@extends('layouts.app')

@section('title', 'Planos')

@section('breadcrumb')
    <x-ui.breadcrumb :items="[['label' => 'Financeiro', 'url' => route('subscription.index')], ['label' => 'Planos']]" />
@endsection

@inject('pricing', \App\Interfaces\Services\PlanPricingInterface::class)

@php
    $isPro = $currentPlan === 'professional';
    $promoActive = $pricing->isPromoActive();
@endphp

@section('content')
<div class="max-w-5xl mx-auto space-y-8">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-surface-900 dark:text-white">Escolha seu plano</h1>
        <p class="text-surface-600 dark:text-surface-400 mt-2 max-w-2xl mx-auto">
            Comece no Básico sem pagar nada e mude para o Profissional quando precisar de mais. Sem fidelidade: você cancela quando quiser.
        </p>
        @if($promoActive)
            <div class="mt-4 flex justify-center">
                <x-billing.promo-countdown />
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
        {{-- Free plan --}}
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border-2 {{ ! $isPro ? 'border-primary-500' : 'border-surface-100 dark:border-surface-800' }} p-6 sm:p-8 relative flex flex-col">
            @if(! $isPro)
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <x-ui.badge variant="neutral" class="shadow-sm">Plano atual</x-ui.badge>
                </div>
            @endif

            <div class="text-center mb-6">
                <span class="icon-box mx-auto mb-4 bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-300"><i class="fas fa-user"></i></span>
                <h2 class="text-2xl font-bold text-surface-900 dark:text-white">{{ $plans['free']['name'] }}</h2>
                <p class="mt-2 num text-4xl font-bold text-surface-900 dark:text-white">Grátis</p>
                <p class="text-sm text-surface-500 dark:text-surface-400 mt-2">{{ $plans['free']['description'] }}</p>
            </div>

            <ul class="space-y-3 mb-8 flex-1">
                @foreach($plans['free']['features'] as $feature)
                    <li class="flex items-start gap-2 text-sm text-surface-700 dark:text-surface-300">
                        <i class="fas fa-check text-primary-500 mt-0.5 shrink-0"></i> {{ $feature }}
                    </li>
                @endforeach
                @foreach($plans['free']['limitations'] ?? [] as $limitation)
                    <li class="flex items-start gap-2 text-sm text-surface-400 dark:text-surface-500">
                        <i class="fas fa-lock mt-0.5 shrink-0"></i> {{ $limitation }}
                    </li>
                @endforeach
            </ul>

            @if(! $isPro)
                <x-ui.button variant="ghost" block disabled aria-disabled="true">Você está neste plano</x-ui.button>
            @else
                <x-ui.button :href="route('subscription.index')" variant="outline" block icon="fa-receipt" title="O cancelamento vale no fim do período pago">
                    Voltar ao Básico pelo Financeiro
                </x-ui.button>
                <p class="text-xs text-center text-surface-500 dark:text-surface-400 mt-2">Agende o cancelamento no Financeiro. Você mantém o Profissional até o fim do período pago.</p>
            @endif
        </div>

        {{-- Professional plan --}}
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-elevated border-2 border-primary-500 dark:border-primary-400 p-6 sm:p-8 relative ring-4 ring-primary-500/10 flex flex-col">
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                @if($isPro)
                    <x-ui.badge variant="success" class="shadow-sm">Plano atual</x-ui.badge>
                @else
                    <x-ui.badge variant="success" icon="fa-star" class="shadow-sm">Recomendado</x-ui.badge>
                @endif
            </div>

            <div class="text-center mb-6">
                <span class="icon-box mx-auto mb-4 bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400"><i class="fas fa-crown"></i></span>
                <h2 class="text-2xl font-bold text-surface-900 dark:text-white">{{ $pricing->planName() }}</h2>
                <div class="mt-2">
                    @if($promoActive)
                        <p class="text-sm text-surface-400 dark:text-surface-500"><span class="line-through">{{ $pricing->formattedRegular() }}</span> <span class="sr-only">preço normal</span></p>
                    @endif
                    <span class="num text-5xl font-bold text-primary-600 dark:text-primary-400">{{ $pricing->formattedCurrent() }}</span>
                    <span class="text-surface-500 dark:text-surface-400">/mês</span>
                </div>
                <p class="text-sm text-surface-500 dark:text-surface-400 mt-2">{{ $plans['professional']['description'] }}</p>
            </div>

            <ul class="space-y-3 mb-8 flex-1">
                @foreach($plans['professional']['features'] as $feature)
                    <li class="flex items-start gap-2 text-sm text-surface-700 dark:text-surface-300">
                        <i class="fas fa-circle-check text-primary-500 mt-0.5 shrink-0"></i> {{ $feature }}
                    </li>
                @endforeach
            </ul>

            @if($isPro)
                <x-ui.button :href="route('subscription.index')" variant="primary" block icon="fa-receipt">Gerenciar no Financeiro</x-ui.button>
            @else
                <form action="{{ route('subscription.checkout') }}" method="POST">
                    @csrf
                    <x-ui.button type="submit" variant="primary" size="lg" block icon="fa-crown" loading="Redirecionando para o pagamento...">
                        Assinar Profissional — {{ $pricing->formattedCurrent() }}/mês
                    </x-ui.button>
                </form>
                <div class="mt-3 text-center space-y-1">
                    <p class="text-xs text-surface-500 dark:text-surface-400"><i class="fas fa-shield-halved text-primary-500 mr-1"></i> Pagamento processado pelo Stripe</p>
                    <p class="text-xs text-surface-500 dark:text-surface-400"><i class="fas fa-rotate-left text-secondary-500 mr-1"></i> Cancele quando quiser; o acesso vale até o fim do período pago</p>
                </div>
            @endif
        </div>
    </div>

    @unless($isPro)
        <div class="rounded-2xl border border-primary-100 dark:border-primary-900/40 bg-primary-50/60 dark:bg-primary-900/10 p-6 text-center">
            <h2 class="text-lg font-bold text-surface-900 dark:text-white mb-2">
                <i class="fas fa-calendar-days text-primary-500 mr-2"></i> Comece hoje e tenha o controle do mês inteiro
            </h2>
            <p class="text-sm text-surface-600 dark:text-surface-300 mb-4 max-w-2xl mx-auto">
                Quanto antes seus lançamentos estiverem organizados, mais completos ficam os relatórios e as análises do mês.
                Assine em minutos e use todos os recursos desde o primeiro dia.
            </p>
            <form action="{{ route('subscription.checkout') }}" method="POST" class="inline-block">
                @csrf
                <x-ui.button type="submit" variant="accent" icon="fa-rocket" loading="Redirecionando para o pagamento...">
                    Quero o Profissional
                </x-ui.button>
            </form>
        </div>
    @endunless

    {{-- FAQ --}}
    <x-ui.section-card title="Perguntas frequentes" icon="fa-circle-question">
        <div class="space-y-5">
            <div>
                <h3 class="font-medium text-surface-900 dark:text-white text-sm">Posso cancelar a qualquer momento?</h3>
                <p class="text-sm text-surface-600 dark:text-surface-400 mt-1">Sim, sem multa. O cancelamento é feito no menu Financeiro e vale no fim do período já pago; até lá, você continua com todos os recursos.</p>
            </div>
            <div>
                <h3 class="font-medium text-surface-900 dark:text-white text-sm">O preço pode mudar?</h3>
                <p class="text-sm text-surface-600 dark:text-surface-400 mt-1">
                    O valor cobrado no início de um período vale para todo esse período. Qualquer mudança de preço é avisada com antecedência, antes da próxima cobrança.
                    @if($promoActive && $pricing->promoEndsAt())
                        O valor promocional de {{ $pricing->formattedCurrent() }} vale para assinaturas feitas até {{ $pricing->promoEndsAt()->format('d/m/Y H:i') }}.
                    @endif
                </p>
            </div>
            <div>
                <h3 class="font-medium text-surface-900 dark:text-white text-sm">O que acontece com meus dados se eu voltar ao plano gratuito?</h3>
                <p class="text-sm text-surface-600 dark:text-surface-400 mt-1">Seus dados são mantidos. Você só não consegue criar novos itens além dos limites do plano Básico.</p>
            </div>
            <div>
                <h3 class="font-medium text-surface-900 dark:text-white text-sm">O pagamento é seguro?</h3>
                <p class="text-sm text-surface-600 dark:text-surface-400 mt-1">Sim. O pagamento acontece na página do Stripe; os dados do seu cartão não passam pelos nossos servidores.</p>
            </div>
        </div>
    </x-ui.section-card>

    <x-ui.tip icon="fa-lightbulb">
        Dica: antes de assinar, teste o Básico com seus lançamentos reais. Quando bater o limite de carteiras ou transações, o Profissional libera tudo sem perder nada do que você já cadastrou.
    </x-ui.tip>
</div>
@endsection
