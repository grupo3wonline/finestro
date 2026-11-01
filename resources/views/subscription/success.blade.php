@extends('layouts.app')

@section('title', 'Assinatura confirmada')

@section('breadcrumb')
    <x-ui.breadcrumb :items="[['label' => 'Financeiro', 'url' => route('subscription.index')], ['label' => 'Assinatura confirmada']]" />
@endsection

@section('content')
<div class="max-w-lg mx-auto text-center py-12">
    <div class="w-20 h-20 bg-primary-50 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-circle-check text-4xl text-primary-500"></i>
    </div>

    <h1 class="text-3xl font-bold text-surface-900 dark:text-white mb-4">Assinatura confirmada!</h1>
    <p class="text-lg text-surface-600 dark:text-surface-400 mb-6">
        Você agora é assinante do plano <strong class="text-primary-600 dark:text-primary-400">Profissional</strong>.
        Todos os recursos estão liberados.
    </p>

    @if($subscription->current_period_end)
        <x-ui.alert variant="success" icon="fa-calendar-check" class="text-left mb-8">
            Vigência atual até <strong>{{ $subscription->current_period_end->format('d/m/Y') }}</strong>. A renovação é automática e você pode cancelar quando quiser pelo menu Financeiro.
        </x-ui.alert>
    @endif

    <div class="bg-white dark:bg-surface-900 rounded-2xl p-6 shadow-card border border-surface-100 dark:border-surface-800 mb-8">
        <h2 class="font-semibold text-surface-900 dark:text-white mb-3">O que você desbloqueou:</h2>
        <ul class="text-sm text-surface-600 dark:text-surface-400 space-y-2 text-left">
            <li><i class="fas fa-check text-primary-500 mr-2"></i> Carteiras ilimitadas</li>
            <li><i class="fas fa-check text-primary-500 mr-2"></i> Transações ilimitadas</li>
            <li><i class="fas fa-check text-primary-500 mr-2"></i> Metas ilimitadas</li>
            <li><i class="fas fa-check text-primary-500 mr-2"></i> Simuladores financeiros</li>
            <li><i class="fas fa-check text-primary-500 mr-2"></i> Exportação de relatórios (CSV)</li>
            <li><i class="fas fa-check text-primary-500 mr-2"></i> Suporte prioritário</li>
        </ul>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <x-ui.button :href="route('dashboard')" variant="primary" icon="fa-tachometer-alt" loading="Carregando...">Ir para o Dashboard</x-ui.button>
        <x-ui.button :href="route('subscription.index')" variant="outline" icon="fa-receipt" loading="Carregando...">Ver Financeiro</x-ui.button>
    </div>
</div>
@endsection
