@extends('layouts.app')

@section('title', 'Simuladores Financeiros')

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
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Simuladores Financeiros</h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Ferramentas para planejar seu futuro financeiro com inteligência.</p>
    </div>

    <!-- Info tip -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <p class="text-sm text-blue-800 dark:text-blue-300">
            <i class="fas fa-lightbulb mr-1"></i>
            <strong>Dica:</strong> Use os simuladores para projetar cenários e tomar decisões financeiras mais informadas. Os cálculos são apenas estimativas e não constituem aconselhamento financeiro.
        </p>
    </div>

    <!-- Simulators Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Investment Simulator -->
        <a href="{{ route('simulators.investment') }}" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition group">
            <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <i class="fas fa-chart-line text-2xl text-green-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Simulador de Investimento</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Calcule o rendimento dos seus investimentos com juros compostos ao longo do tempo.</p>
            <span class="text-primary-600 text-sm font-medium">Simular <i class="fas fa-arrow-right ml-1"></i></span>
        </a>

        <!-- Retirement Simulator -->
        <a href="{{ route('simulators.retirement') }}" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition group">
            <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <i class="fas fa-umbrella-beach text-2xl text-purple-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Simulador de Aposentadoria</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Descubra quanto precisa poupar mensalmente para se aposentar com tranquilidade.</p>
            <span class="text-primary-600 text-sm font-medium">Simular <i class="fas fa-arrow-right ml-1"></i></span>
        </a>

        <!-- Budget Simulator -->
        <a href="{{ route('simulators.budget') }}" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition group">
            <div class="w-14 h-14 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <i class="fas fa-calculator text-2xl text-orange-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Simulador de Orçamento</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Planeje seu orçamento mensal usando a regra 50-30-20 e veja como distribuir sua renda.</p>
            <span class="text-primary-600 text-sm font-medium">Simular <i class="fas fa-arrow-right ml-1"></i></span>
        </a>
    </div>
</div>
@endsection
