@extends('layouts.app')

@section('title', 'Guia de Teste - Stripe')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('subscription.index') }}" class="text-gray-500 hover:text-primary-600">Assinatura</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Guia de Teste</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fab fa-stripe text-primary-500 mr-2"></i> Guia de Teste - Stripe
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Use estas informações para testar o fluxo de assinatura.</p>
    </div>

    <!-- Test Card Info -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 border-b border-primary-200 dark:border-primary-800">
            <h2 class="text-lg font-semibold text-primary-900 dark:text-primary-100">
                <i class="fas fa-credit-card mr-2"></i> Cartão de Teste
            </h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Número do Cartão</label>
                    <p class="text-lg font-mono font-bold text-gray-900 dark:text-white tracking-wider">4242 4242 4242 4242</p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Validade</label>
                    <p class="text-lg font-mono font-bold text-gray-900 dark:text-white">12/34</p>
                    <p class="text-xs text-gray-500 mt-1">Qualquer data futura</p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">CVC</label>
                    <p class="text-lg font-mono font-bold text-gray-900 dark:text-white">123</p>
                    <p class="text-xs text-gray-500 mt-1">Qualquer 3 dígitos</p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">CEP</label>
                    <p class="text-lg font-mono font-bold text-gray-900 dark:text-white">12345</p>
                    <p class="text-xs text-gray-500 mt-1">Qualquer CEP válido</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Flow -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-list-ol text-secondary-500 mr-2"></i> Fluxo de Teste
            </h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="flex items-start gap-4">
                <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-primary-600">1</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Acesse a página de planos</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Vá em <a href="{{ route('subscription.plans') }}" class="text-primary-600 hover:underline">Assinatura &rarr; Planos</a></p>
                </div>
            </div>
            <div class="flex items-start gap-4">
                <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-primary-600">2</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Clique em "Assinar Agora"</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Você será redirecionado para o checkout do Stripe.</p>
                </div>
            </div>
            <div class="flex items-start gap-4">
                <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-primary-600">3</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Preencha com o cartão de teste</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Use <code class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded text-xs font-mono">4242 4242 4242 4242</code>, qualquer data futura e qualquer CVC.</p>
                </div>
            </div>
            <div class="flex items-start gap-4">
                <div class="w-8 h-8 bg-secondary-100 dark:bg-secondary-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-secondary-600">4</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Confirme o pagamento</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Após confirmação, você será redirecionado de volta com o plano Profissional ativo.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Check -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-check-circle text-secondary-500 mr-2"></i> Status Atual
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Plano Atual</label>
                    <p class="text-lg font-bold {{ Auth::user()->isProfessional() ? 'text-primary-600' : 'text-gray-600' }}">
                        {{ Auth::user()->isProfessional() ? 'Profissional' : 'Básico (Gratuito)' }}
                    </p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Stripe Configurado</label>
                    @if(config('stripe.secret_key') && config('stripe.plans.professional.stripe_price_id'))
                        <p class="text-lg font-bold text-secondary-600"><i class="fas fa-check mr-1"></i> Sim</p>
                    @else
                        <p class="text-lg font-bold text-red-600"><i class="fas fa-times mr-1"></i> Não</p>
                        <p class="text-xs text-red-500 mt-1">Configure STRIPE_SECRET_KEY e STRIPE_PROFESSIONAL_PRICE_ID no .env</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col sm:flex-row gap-3">
        <a href="{{ route('subscription.plans') }}" class="inline-flex items-center justify-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition">
            <i class="fas fa-bolt mr-2"></i> Ir para Planos
        </a>
        <a href="{{ route('subscription.index') }}" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            <i class="fas fa-receipt mr-2"></i> Ver Assinatura
        </a>
    </div>
</div>
@endsection
