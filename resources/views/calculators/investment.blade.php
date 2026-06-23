@extends('layouts.app')

@section('title', 'Calculadora de Investimento')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('calculators.index') }}" class="text-gray-500 hover:text-primary-600">Calculadoras</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Investimento</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="investmentCalculator()">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-chart-line text-green-500 mr-2"></i> Calculadora de Investimento
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Calcule o rendimento dos seus investimentos com aportes mensais e juros compostos.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Valor Inicial (R$)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Quanto você já tem para investir hoje"></i>
                </label>
                <input type="number" x-model.number="initialAmount" min="0" step="100" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 1000">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Aporte Mensal (R$)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Valor que você vai investir todo mês"></i>
                </label>
                <input type="number" x-model.number="monthlyDeposit" min="0" step="50" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Taxa de Juros (% ao mês)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Rentabilidade mensal esperada do investimento"></i>
                </label>
                <input type="number" x-model.number="monthlyRate" min="0" max="10" step="0.01" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 1.0">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Período (meses)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Por quantos meses pretende investir"></i>
                </label>
                <input type="number" x-model.number="months" min="1" max="600" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 60">
            </div>

            <div class="flex gap-3">
                <button @click="calculate()" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">
                    <i class="fas fa-calculator mr-2"></i> Calcular
                </button>
                <button @click="reset()" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition">
                    <i class="fas fa-eraser mr-1"></i> Limpar
                </button>
            </div>
        </div>

        <!-- Results -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Resultado</h3>

            <template x-if="result">
                <div class="space-y-4">
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center border border-green-200 dark:border-green-800">
                        <p class="text-sm text-green-700 dark:text-green-400">Montante Final</p>
                        <p class="text-3xl font-bold text-green-600" x-text="formatCurrency(result.finalAmount)"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Investido</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(result.totalInvested)"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total de Juros</p>
                            <p class="text-lg font-semibold text-green-600" x-text="formatCurrency(result.totalInterest)"></p>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Rentabilidade Total</p>
                        <p class="text-lg font-semibold text-primary-600" x-text="result.profitPercent.toFixed(2) + '%'"></p>
                    </div>
                </div>
            </template>

            <template x-if="!result">
                <div class="text-center py-12">
                    <i class="fas fa-chart-line text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">Preencha os dados e clique em calcular.</p>
                </div>
            </template>
        </div>
    </div>

    <!-- Monthly Table -->
    <template x-if="result && result.table.length > 0">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Evolução Mensal</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 px-3 text-gray-600 dark:text-gray-400">Mês</th>
                            <th class="text-right py-2 px-3 text-gray-600 dark:text-gray-400">Aporte</th>
                            <th class="text-right py-2 px-3 text-gray-600 dark:text-gray-400">Juros no Mês</th>
                            <th class="text-right py-2 px-3 text-gray-600 dark:text-gray-400">Saldo Acumulado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in result.table" :key="row.month">
                            <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                <td class="py-2 px-3 text-gray-900 dark:text-white" x-text="row.month"></td>
                                <td class="py-2 px-3 text-right text-gray-700 dark:text-gray-300" x-text="formatCurrency(row.deposit)"></td>
                                <td class="py-2 px-3 text-right text-green-600" x-text="formatCurrency(row.interest)"></td>
                                <td class="py-2 px-3 text-right font-medium text-gray-900 dark:text-white" x-text="formatCurrency(row.balance)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <p x-show="months > 24" class="text-xs text-gray-500 dark:text-gray-400 mt-3 text-center">
                <i class="fas fa-info-circle mr-1"></i> Exibindo os primeiros 12 e últimos 12 meses do período.
            </p>
        </div>
    </template>

    <!-- Formula -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6" x-data="{ showFormula: false }">
        <button @click="showFormula = !showFormula" class="flex items-center justify-between w-full text-left">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-book-open text-primary-500 mr-2"></i> Fórmula Utilizada
            </h3>
            <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="{ 'rotate-180': showFormula }"></i>
        </button>
        <div x-show="showFormula" x-transition class="mt-4 space-y-3">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <p class="font-mono text-sm text-gray-800 dark:text-gray-200">
                    M = P × (1 + i)ⁿ + PMT × [((1 + i)ⁿ - 1) / i]
                </p>
            </div>
            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                <li><strong>M</strong> = Montante final</li>
                <li><strong>P</strong> = Principal (valor inicial)</li>
                <li><strong>i</strong> = Taxa de juros mensal (decimal)</li>
                <li><strong>n</strong> = Número de meses</li>
                <li><strong>PMT</strong> = Aporte mensal</li>
            </ul>
        </div>
    </div>
</div>

@push('scripts')
<script>
function investmentCalculator() {
    return {
        initialAmount: 1000,
        monthlyDeposit: 500,
        monthlyRate: 1.0,
        months: 60,
        result: null,

        calculate() {
            const i = this.monthlyRate / 100;
            const n = this.months;
            const P = this.initialAmount || 0;
            const PMT = this.monthlyDeposit || 0;

            let balance = P;
            let table = [];

            for (let m = 1; m <= n; m++) {
                const interest = balance * i;
                balance = balance + interest + PMT;
                table.push({
                    month: m,
                    deposit: m === 1 ? P + PMT : PMT,
                    interest: interest,
                    balance: balance
                });
            }

            // Show first 12 and last 12 if period > 24
            let displayTable = table;
            if (n > 24) {
                displayTable = [...table.slice(0, 12), ...table.slice(-12)];
            }

            const totalInvested = P + (PMT * n);
            const totalInterest = balance - totalInvested;
            const profitPercent = totalInvested > 0 ? (totalInterest / totalInvested) * 100 : 0;

            this.result = {
                finalAmount: balance,
                totalInvested: totalInvested,
                totalInterest: totalInterest,
                profitPercent: profitPercent,
                table: displayTable
            };
        },

        reset() {
            this.initialAmount = 1000;
            this.monthlyDeposit = 500;
            this.monthlyRate = 1.0;
            this.months = 60;
            this.result = null;
        },

        formatCurrency(value) {
            return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }
}
</script>
@endpush
@endsection
