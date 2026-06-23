@extends('layouts.app')

@section('title', 'Calculadora de Juros Compostos')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('calculators.index') }}" class="text-gray-500 hover:text-primary-600">Calculadoras</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Juros Compostos</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="interestCalculator()">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-percent text-blue-500 mr-2"></i> Calculadora de Juros Compostos
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Compare juros simples e compostos e veja o poder dos juros sobre juros.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Capital Inicial (R$)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Valor que será aplicado inicialmente"></i>
                </label>
                <input type="number" x-model.number="capital" min="0" step="100" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 10000">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Taxa de Juros (%)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Percentual de juros aplicado no período"></i>
                </label>
                <div class="flex gap-2">
                    <input type="number" x-model.number="rate" min="0" max="100" step="0.01" autocomplete="off"
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                        placeholder="Ex: 1.0">
                    <select x-model="rateType" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
                        <option value="monthly">ao mês</option>
                        <option value="yearly">ao ano</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Período
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Duração da aplicação"></i>
                </label>
                <div class="flex gap-2">
                    <input type="number" x-model.number="period" min="1" max="600" autocomplete="off"
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                        placeholder="Ex: 12">
                    <select x-model="periodType" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
                        <option value="months">meses</option>
                        <option value="years">anos</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3">
                <button @click="calculate()" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
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
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-blue-700 dark:text-blue-400">Montante (Juros Compostos)</p>
                        <p class="text-3xl font-bold text-blue-600" x-text="formatCurrency(result.compoundAmount)"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Juros Ganhos</p>
                            <p class="text-lg font-semibold text-blue-600" x-text="formatCurrency(result.compoundInterest)"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Rentabilidade Total</p>
                            <p class="text-lg font-semibold text-primary-600" x-text="result.compoundPercent.toFixed(2) + '%'"></p>
                        </div>
                    </div>

                    <!-- Comparison -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Comparativo: Simples vs Compostos</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3 text-center border border-orange-200 dark:border-orange-800">
                                <p class="text-xs text-orange-700 dark:text-orange-400">Juros Simples</p>
                                <p class="text-base font-semibold text-orange-600" x-text="formatCurrency(result.simpleAmount)"></p>
                            </div>
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-center border border-blue-200 dark:border-blue-800">
                                <p class="text-xs text-blue-700 dark:text-blue-400">Juros Compostos</p>
                                <p class="text-base font-semibold text-blue-600" x-text="formatCurrency(result.compoundAmount)"></p>
                            </div>
                        </div>
                        <div class="mt-3 bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center border border-green-200 dark:border-green-800">
                            <p class="text-xs text-green-700 dark:text-green-400">Diferença a favor dos Compostos</p>
                            <p class="text-lg font-bold text-green-600" x-text="formatCurrency(result.difference)"></p>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="!result">
                <div class="text-center py-12">
                    <i class="fas fa-percent text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">Preencha os dados e clique em calcular.</p>
                </div>
            </template>
        </div>
    </div>

    <!-- Formula -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6" x-data="{ showFormula: false }">
        <button @click="showFormula = !showFormula" class="flex items-center justify-between w-full text-left">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-book-open text-primary-500 mr-2"></i> Fórmulas Utilizadas
            </h3>
            <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="{ 'rotate-180': showFormula }"></i>
        </button>
        <div x-show="showFormula" x-transition class="mt-4 space-y-4">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 space-y-2">
                <p class="font-mono text-sm text-gray-800 dark:text-gray-200">
                    <strong>Juros Compostos:</strong> M = C × (1 + i)ⁿ
                </p>
                <p class="font-mono text-sm text-gray-800 dark:text-gray-200">
                    <strong>Juros Simples:</strong> M = C × (1 + i × n)
                </p>
            </div>
            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                <li><strong>M</strong> = Montante final</li>
                <li><strong>C</strong> = Capital inicial</li>
                <li><strong>i</strong> = Taxa de juros (decimal)</li>
                <li><strong>n</strong> = Número de períodos</li>
            </ul>
        </div>
    </div>
</div>

@push('scripts')
<script>
function interestCalculator() {
    return {
        capital: 10000,
        rate: 1.0,
        rateType: 'monthly',
        period: 12,
        periodType: 'months',
        result: null,

        calculate() {
            const C = this.capital || 0;
            let i = this.rate / 100;
            let n = this.period || 1;

            // Convert to monthly basis for calculation
            if (this.rateType === 'yearly' && this.periodType === 'months') {
                i = Math.pow(1 + i, 1/12) - 1;
            } else if (this.rateType === 'monthly' && this.periodType === 'years') {
                n = n * 12;
            } else if (this.rateType === 'yearly' && this.periodType === 'years') {
                n = n * 12;
                i = Math.pow(1 + i, 1/12) - 1;
            }

            // Compound interest
            const compoundAmount = C * Math.pow(1 + i, n);
            const compoundInterest = compoundAmount - C;
            const compoundPercent = C > 0 ? (compoundInterest / C) * 100 : 0;

            // Simple interest
            const simpleAmount = C * (1 + i * n);
            const simpleInterest = simpleAmount - C;

            const difference = compoundAmount - simpleAmount;

            this.result = {
                compoundAmount,
                compoundInterest,
                compoundPercent,
                simpleAmount,
                simpleInterest,
                difference
            };
        },

        reset() {
            this.capital = 10000;
            this.rate = 1.0;
            this.rateType = 'monthly';
            this.period = 12;
            this.periodType = 'months';
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
