@extends('layouts.app')

@section('title', 'Simuladores - Plano Profissional')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Simuladores</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-2xl mx-auto text-center py-16">
    <div class="w-20 h-20 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-lock text-3xl text-yellow-500"></i>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Recurso Exclusivo do Plano Profissional</h1>
    <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
        Os simuladores financeiros estão disponíveis apenas para assinantes do plano Profissional. Faça upgrade para acessar simuladores de investimento, aposentadoria e orçamento.
    </p>

    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-8 max-w-md mx-auto">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Com o plano Profissional você tem:</h3>
        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2 text-left">
            <li><i class="fas fa-check text-green-500 mr-2"></i> Simulador de Investimento</li>
            <li><i class="fas fa-check text-green-500 mr-2"></i> Simulador de Aposentadoria</li>
            <li><i class="fas fa-check text-green-500 mr-2"></i> Simulador de Orçamento</li>
            <li><i class="fas fa-check text-green-500 mr-2"></i> Recursos ilimitados</li>
            <li><i class="fas fa-check text-green-500 mr-2"></i> Exportação de relatórios</li>
        </ul>
    </div>

    <a href="{{ route('subscription.plans') }}" class="inline-flex items-center px-8 py-3 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl transition shadow-lg">
        <i class="fas fa-crown mr-2"></i> Fazer Upgrade — <span class="line-through text-primary-200 mr-1">R$ 41,58</span> R$ 24,90/mês
    </a>
    <p class="text-xs text-red-500 mt-2 font-semibold"><i class="fas fa-hourglass-half mr-1"></i> Promoção por tempo limitado — 40% OFF</p>
</div>
@endsection
