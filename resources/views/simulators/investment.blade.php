@extends('layouts.app')

@section('title', 'Simulador de Investimento')

@section('breadcrumb')
<x-ui.breadcrumb :items="[
    ['label' => 'Simuladores', 'url' => route('simulators.index')],
    ['label' => 'Investimento'],
]" />
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="investmentSimulator()">
    <div>
        <h1 class="text-2xl font-bold text-surface-900 dark:text-white">
            <i class="fas fa-chart-line text-green-500 mr-2"></i> Simulador de Investimento
        </h1>
        <p class="text-surface-600 dark:text-surface-400 text-sm">Calcule o rendimento dos seus investimentos com juros compostos.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Form -->
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Valor Inicial (R$)</label>
                <input type="number" x-model.number="initialAmount" min="0" step="100" autocomplete="off"
                    class="w-full px-4 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="1000">
            </div>

            <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Aporte Mensal (R$)</label>
                <input type="number" x-model.number="monthlyContribution" min="0" step="50" autocomplete="off"
                    class="w-full px-4 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="500">
            </div>

            <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Taxa de Juros Anual (%)</label>
                <input type="number" x-model.number="annualRate" min="0" max="100" step="0.1" autocomplete="off"
                    class="w-full px-4 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="12">
            </div>

            <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Período (anos)</label>
                <input type="number" x-model.number="years" min="1" max="50" autocomplete="off"
                    class="w-full px-4 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="10">
            </div>

            <button @click="calculate()" class="w-full px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition">
                <i class="fas fa-calculator mr-2"></i> Calcular
            </button>
        </div>

        <!-- Results -->
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-6">
            <h3 class="text-lg font-semibold text-surface-900 dark:text-white mb-4">Resultado da Simulação</h3>

            <template x-if="result">
                <div class="space-y-4">
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                        <p class="text-sm text-green-700 dark:text-green-400">Valor Final</p>
                        <p class="text-3xl font-bold text-green-600" x-text="formatCurrency(result.finalAmount)"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-surface-50 dark:bg-surface-800/60 rounded-lg p-3 text-center">
                            <p class="text-xs text-surface-500 dark:text-surface-400">Total Investido</p>
                            <p class="text-lg font-semibold text-surface-900 dark:text-white" x-text="formatCurrency(result.totalInvested)"></p>
                        </div>
                        <div class="bg-surface-50 dark:bg-surface-800/60 rounded-lg p-3 text-center">
                            <p class="text-xs text-surface-500 dark:text-surface-400">Juros Ganhos</p>
                            <p class="text-lg font-semibold text-green-600" x-text="formatCurrency(result.totalInterest)"></p>
                        </div>
                    </div>

                    <div class="bg-surface-50 dark:bg-surface-800/60 rounded-lg p-3 text-center">
                        <p class="text-xs text-surface-500 dark:text-surface-400">Rendimento Total</p>
                        <p class="text-lg font-semibold text-primary-600" x-text="result.percentGain.toFixed(2) + '%'"></p>
                    </div>
                </div>
            </template>

            <template x-if="!result">
                <div class="text-center py-12">
                    <i class="fas fa-chart-line text-4xl text-surface-300 dark:text-surface-600 mb-3"></i>
                    <p class="text-surface-500 dark:text-surface-400">Preencha os dados e clique em calcular para ver o resultado.</p>
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
