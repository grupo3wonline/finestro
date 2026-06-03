@extends('layouts.landing')

@section('title', 'Ferramentas')
@section('meta_description', 'Ferramentas financeiras do Finestro: simuladores, calculadoras e utilitários para melhorar sua gestão financeira.')

@section('content')
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8 text-sm" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
                <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
                <li class="text-gray-700 dark:text-gray-300 font-medium">Ferramentas</li>
            </ol>
        </nav>

        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">Ferramentas Financeiras</h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Utilitários e calculadoras para ajudar você a tomar decisões financeiras mais inteligentes.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-white dark:bg-gray-900 rounded-xl p-8 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-primary-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Simulador de Investimentos</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Calcule o rendimento dos seus investimentos ao longo do tempo. Insira o valor inicial, aportes mensais, taxa de juros e prazo para visualizar o crescimento do seu patrimônio.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500 italic">
                    <i class="fas fa-lightbulb text-yellow-500 mr-1"></i> Dica: Comece com pequenos aportes mensais. O poder dos juros compostos faz a diferença no longo prazo.
                </p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl p-8 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-secondary-100 dark:bg-secondary-900/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-umbrella-beach text-secondary-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Calculadora de Aposentadoria</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Descubra quanto você precisa poupar mensalmente para se aposentar com tranquilidade. Considere sua idade atual, idade desejada de aposentadoria e renda mensal desejada.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500 italic">
                    <i class="fas fa-lightbulb text-yellow-500 mr-1"></i> Dica: Quanto mais cedo começar, menor será o esforço mensal necessário.
                </p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl p-8 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-balance-scale text-purple-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Planejador de Orçamento</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Monte seu orçamento mensal ideal distribuindo sua renda entre categorias essenciais, desejos e investimentos de forma equilibrada.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500 italic">
                    <i class="fas fa-lightbulb text-yellow-500 mr-1"></i> Dica: Use a regra 50-30-20 como ponto de partida e ajuste conforme sua realidade.
                </p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl p-8 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-percentage text-orange-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Calculadora de Juros</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Compare juros simples e compostos, calcule o custo real de empréstimos e financiamentos antes de tomar decisões.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500 italic">
                    <i class="fas fa-lightbulb text-yellow-500 mr-1"></i> Dica: Sempre compare o CET (Custo Efetivo Total) entre diferentes opções de crédito.
                </p>
            </div>
        </div>

        <div class="mt-12 text-center">
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                <i class="fas fa-info-circle text-primary-500 mr-1"></i>
                Crie sua conta gratuita para acessar todas as ferramentas e simuladores.
            </p>
            <a href="{{ route('register') }}" class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl transition">
                <i class="fas fa-rocket mr-2"></i> Acessar Ferramentas
            </a>
        </div>
    </div>
</section>

<!-- CTA -->
@include('components.cta.main')
@endsection
