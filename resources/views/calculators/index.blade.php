@extends('layouts.app')

@section('title', 'Calculadoras Financeiras')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Calculadoras</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Calculadoras Financeiras</h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Ferramentas gratuitas para cálculos financeiros do dia a dia.</p>
    </div>

    <!-- Info tip -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <p class="text-sm text-blue-800 dark:text-blue-300">
            <i class="fas fa-lightbulb mr-1"></i>
            <strong>Dica:</strong> As calculadoras são 100% gratuitas e funcionam localmente no seu navegador. Nenhum dado é enviado para servidores externos.
        </p>
    </div>

    <!-- Calculators Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Investment Calculator -->
        <a href="{{ route('calculators.investment') }}" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-green-300 dark:hover:border-green-700 transition group">
            <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <i class="fas fa-chart-line text-2xl text-green-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Investimento</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Calcule o rendimento de investimentos com aportes mensais e juros compostos.</p>
            <span class="text-green-600 text-sm font-medium">Calcular <i class="fas fa-arrow-right ml-1"></i></span>
        </a>

        <!-- Compound Interest Calculator -->
        <a href="{{ route('calculators.interest') }}" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition group">
            <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <i class="fas fa-percent text-2xl text-blue-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Juros Compostos</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Compare juros simples e compostos e veja o poder dos juros sobre juros.</p>
            <span class="text-blue-600 text-sm font-medium">Calcular <i class="fas fa-arrow-right ml-1"></i></span>
        </a>

        <!-- Retirement Calculator -->
        <a href="{{ route('calculators.retirement') }}" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 transition group">
            <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <i class="fas fa-umbrella-beach text-2xl text-purple-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Aposentadoria</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Descubra quanto precisa investir por mês para se aposentar com a renda desejada.</p>
            <span class="text-purple-600 text-sm font-medium">Calcular <i class="fas fa-arrow-right ml-1"></i></span>
        </a>

        <!-- 13th Salary Calculator -->
        <a href="{{ route('calculators.thirteenth-salary') }}" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-orange-300 dark:hover:border-orange-700 transition group">
            <div class="w-14 h-14 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <i class="fas fa-money-bill-wave text-2xl text-orange-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">13º Salário</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Calcule as parcelas do 13º com descontos de INSS e IRRF atualizados.</p>
            <span class="text-orange-600 text-sm font-medium">Calcular <i class="fas fa-arrow-right ml-1"></i></span>
        </a>

        <!-- Vacation Calculator -->
        <a href="{{ route('calculators.vacation') }}" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-teal-300 dark:hover:border-teal-700 transition group">
            <div class="w-14 h-14 bg-teal-100 dark:bg-teal-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <i class="fas fa-plane-departure text-2xl text-teal-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Férias</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Calcule o valor líquido das suas férias com abono pecuniário e descontos.</p>
            <span class="text-teal-600 text-sm font-medium">Calcular <i class="fas fa-arrow-right ml-1"></i></span>
        </a>

        <!-- Financing Calculator -->
        <a href="{{ route('calculators.financing') }}" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-red-300 dark:hover:border-red-700 transition group">
            <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <i class="fas fa-home text-2xl text-red-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Financiamento</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Compare financiamentos nas tabelas SAC e PRICE e veja qual é mais vantajoso.</p>
            <span class="text-red-600 text-sm font-medium">Calcular <i class="fas fa-arrow-right ml-1"></i></span>
        </a>

        <!-- Currency Exchange -->
        <a href="{{ route('calculators.currency') }}" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition group">
            <div class="w-14 h-14 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <i class="fas fa-exchange-alt text-2xl text-amber-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Cotação de Moedas</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Cotações comerciais em tempo real e conversor de moedas com taxas atualizadas.</p>
            <span class="text-amber-600 text-sm font-medium">Ver cotações <i class="fas fa-arrow-right ml-1"></i></span>
        </a>
    </div>
</div>
@endsection
