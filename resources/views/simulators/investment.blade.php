@extends('layouts.app')

@section('title', 'Simulador de Investimento')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('simulators.index') }}" class="text-gray-500 hover:text-primary-600">Simuladores</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Investimento</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="investmentSimulator()">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-chart-line text-green-500 mr-2"></i> Simulador de Investimento
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Calcule o rendimento dos seus investimentos com juros compostos.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor Inicial (R$)</label>
                <input type="number" x-model.number="initialAmount" min="0" step="100" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="1000">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aporte Mensal (R$)</label>
                <input type="number" x-model.number="monthlyContribution" min="0" step="50" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Taxa de Juros Anual (%)</label>
                <input type="number" x-model.number="annualRate" min="0" max="100" step="0.1" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="12">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Período (anos)</label>
                <input type="number" x-model.number="years" min="1" max="50" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="10">
            </div>

            <button @click="calculate()" class="w-full px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition">
                <i class="fas fa-calculator mr-2"></i> Calcular
            </button>
        </div>

        <!-- Results -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Resultado da Simulação</h3>

            <template x-if="result">
                <div class="space-y-4">
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                        <p class="text-sm text-green-700 dark:text-green-400">Valor Final</p>
                        <p class="text-3xl font-bold text-green-600" x-text="formatCurrency(result.finalAmount)"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Investido</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(result.totalInvested)"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Juros Ganhos</p>
                            <p class="text-lg font-semibold text-green-600" x-text="formatCurrency(result.totalInterest)"></p>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Rendimento Total</p>
                        <p class="text-lg font-semibold text-primary-600" x-text="result.percentGain.toFixed(2) + '%'"></p>
                    </div>
                </div>
            </template>

            <template x-if="!result">
                <div class="text-center py-12">
                    <i class="fas fa-chart-line text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">Preencha os dados e clique em calcular para ver o resultado.</p>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function investmentSimulator() {
    return {
        initialAmount: 1000,
        monthlyContribution: 500,
        annualRate: 12,
        years: 10,
        result: null,

        calculate() {
            const monthlyRate = this.annualRate / 100 / 12;
            const months = this.years * 12;
            let balance = this.initialAmount;

            for (let i = 0; i < months; i++) {
                balance = balance * (1 + monthlyRate) + this.monthlyContribution;
            }

            const totalInvested = this.initialAmount + (this.monthlyContribution * months);
            const totalInterest = balance - totalInvested;
            const percentGain = totalInvested > 0 ? (totalInterest / totalInvested) * 100 : 0;

            this.result = {
                finalAmount: balance,
                totalInvested: totalInvested,
                totalInterest: totalInterest,
                percentGain: percentGain
            };
        },

        formatCurrency(value) {
            return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }
}
</script>
@endpush
@endsection
