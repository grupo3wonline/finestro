@extends('layouts.app')

@section('title', 'Planos')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('subscription.index') }}" class="text-gray-500 hover:text-primary-600">Assinatura</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Planos</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Escolha seu Plano</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Selecione o plano ideal para suas necessidades financeiras.</p>
    </div>

    <div class="grid md:grid-cols-2 gap-8 mt-8">
        <!-- Free Plan -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border-2 {{ $currentPlan === 'free' ? 'border-primary-500' : 'border-gray-200 dark:border-gray-700' }} p-8 relative">
            @if($currentPlan === 'free')
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                <span class="bg-primary-600 text-white text-xs font-bold px-3 py-1 rounded-full">PLANO ATUAL</span>
            </div>
            @endif

            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user text-2xl text-gray-600 dark:text-gray-300"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Básico</h3>
                <div class="mt-2">
                    <span class="text-4xl font-bold text-gray-900 dark:text-white">Grátis</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Para sempre</p>
            </div>

            <ul class="space-y-3 mb-8">
                @foreach($plans['free']['features'] as $feature)
                <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <i class="fas fa-check text-green-500 flex-shrink-0"></i>
                    {{ $feature }}
                </li>
                @endforeach
                <li class="flex items-center gap-2 text-sm text-gray-400">
                    <i class="fas fa-times text-red-400 flex-shrink-0"></i>
                    Simuladores financeiros
                </li>
                <li class="flex items-center gap-2 text-sm text-gray-400">
                    <i class="fas fa-times text-red-400 flex-shrink-0"></i>
                    Exportação de relatórios
                </li>
            </ul>

            @if($currentPlan === 'free')
            <button disabled class="w-full px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-medium rounded-xl cursor-not-allowed">
                Plano Atual
            </button>
            @else
            <p class="text-center text-sm text-gray-500">Cancele sua assinatura para voltar ao plano gratuito.</p>
            @endif
        </div>

        <!-- Professional Plan -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border-2 {{ $currentPlan === 'professional' ? 'border-primary-500' : 'border-primary-200 dark:border-primary-800' }} p-8 relative">
            @if($currentPlan === 'professional')
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                <span class="bg-primary-600 text-white text-xs font-bold px-3 py-1 rounded-full">PLANO ATUAL</span>
            </div>
            @else
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                <span class="bg-secondary-500 text-white text-xs font-bold px-3 py-1 rounded-full">RECOMENDADO</span>
            </div>
            @endif

            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-crown text-2xl text-primary-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Profissional</h3>
                <div class="mt-2">
                    <span class="text-4xl font-bold text-gray-900 dark:text-white">R$ 29,90</span>
                    <span class="text-gray-500 dark:text-gray-400">/mês</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Cancele quando quiser</p>
            </div>

            <ul class="space-y-3 mb-8">
                @foreach($plans['professional']['features'] as $feature)
                <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <i class="fas fa-check text-green-500 flex-shrink-0"></i>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>

            @if($currentPlan === 'professional')
            <button disabled class="w-full px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-medium rounded-xl cursor-not-allowed">
                Plano Atual
            </button>
            @else
            <form action="{{ route('subscription.checkout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl transition shadow-lg">
                    <i class="fas fa-bolt mr-2"></i> Assinar Agora
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- FAQ -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-question-circle text-primary-500 mr-2"></i> Perguntas Frequentes
        </h3>
        <div class="space-y-4">
            <div>
                <h4 class="font-medium text-gray-900 dark:text-white text-sm">Posso cancelar a qualquer momento?</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Sim! Você pode cancelar quando quiser. O acesso ao plano profissional continua até o fim do período pago.</p>
            </div>
            <div>
                <h4 class="font-medium text-gray-900 dark:text-white text-sm">O que acontece com meus dados se eu voltar ao plano gratuito?</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Seus dados são mantidos. Porém, você não poderá criar novos recursos além dos limites do plano gratuito.</p>
            </div>
            <div>
                <h4 class="font-medium text-gray-900 dark:text-white text-sm">O pagamento é seguro?</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Sim! Utilizamos o Stripe, uma das plataformas de pagamento mais seguras do mundo. Seus dados de cartão nunca passam pelo nosso servidor.</p>
            </div>
        </div>
    </div>
</div>
@endsection
