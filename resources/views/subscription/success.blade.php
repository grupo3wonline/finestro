@extends('layouts.app')

@section('title', 'Assinatura Confirmada')

@section('content')
<div class="max-w-lg mx-auto text-center py-16">
    <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-check-circle text-4xl text-green-500"></i>
    </div>

    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Assinatura Confirmada!</h1>
    <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
        Parabéns! Você agora é um assinante do plano <strong class="text-primary-600">Profissional</strong>.
        Todos os recursos estão desbloqueados.
    </p>

    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">O que você desbloqueou:</h3>
        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2 text-left">
            <li><i class="fas fa-check text-green-500 mr-2"></i> Carteiras ilimitadas</li>
            <li><i class="fas fa-check text-green-500 mr-2"></i> Transações ilimitadas</li>
            <li><i class="fas fa-check text-green-500 mr-2"></i> Metas ilimitadas</li>
            <li><i class="fas fa-check text-green-500 mr-2"></i> Simuladores financeiros</li>
            <li><i class="fas fa-check text-green-500 mr-2"></i> Exportação de relatórios (CSV)</li>
            <li><i class="fas fa-check text-green-500 mr-2"></i> Suporte prioritário</li>
        </ul>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition">
            <i class="fas fa-tachometer-alt mr-2"></i> Ir para o Dashboard
        </a>
        <a href="{{ route('subscription.index') }}" class="inline-flex items-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            <i class="fas fa-receipt mr-2"></i> Ver Assinatura
        </a>
    </div>
</div>
@endsection
