@extends('layouts.landing')

@section('title', 'Serviços')
@section('meta_description', 'Conheça os serviços do Finestro: gestão de carteiras, controle de transações, metas financeiras e simuladores.')

@section('content')
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8 text-sm" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
                <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
                <li class="text-gray-700 dark:text-gray-300 font-medium">Serviços</li>
            </ol>
        </nav>

        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">Nossos Serviços</h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Soluções completas para você ter controle total das suas finanças pessoais.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Service Cards -->
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition">
                <div class="w-14 h-14 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-wallet text-2xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Gestão de Carteiras</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Crie e gerencie múltiplas carteiras: conta corrente, poupança, investimentos, cartão de crédito e dinheiro em espécie.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Saldos em tempo real</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Personalização com cores e ícones</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Ativação/desativação flexível</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition">
                <div class="w-14 h-14 bg-secondary-100 dark:bg-secondary-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-exchange-alt text-2xl text-secondary-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Controle de Transações</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Registre todas as suas receitas e despesas com categorização inteligente e anexo de comprovantes.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Categorias personalizáveis</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Transações recorrentes</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Anexo de comprovantes</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition">
                <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-bullseye text-2xl text-purple-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Metas Financeiras</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Defina objetivos financeiros com prazos e acompanhe o progresso visual até alcançá-los.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Barra de progresso visual</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Prazos e lembretes</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Histórico de conquistas</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition">
                <div class="w-14 h-14 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-chart-bar text-2xl text-orange-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Dashboard Inteligente</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Visualize sua saúde financeira com gráficos interativos e comparativos de períodos.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Gráficos interativos</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Comparativo mensal</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Resumo financeiro</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition">
                <div class="w-14 h-14 bg-teal-100 dark:bg-teal-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-calculator text-2xl text-teal-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Simuladores</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Simule investimentos, aposentadoria e orçamentos para tomar decisões mais informadas.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Simulador de investimentos</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Planejamento de aposentadoria</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Calculadora de orçamento</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition">
                <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-file-export text-2xl text-red-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Relatórios e Exportação</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Gere relatórios detalhados e exporte seus dados em PDF ou CSV para análise externa.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Exportação PDF/CSV</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Filtros avançados</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Relatórios por período</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
@include('components.cta.main')
@endsection
