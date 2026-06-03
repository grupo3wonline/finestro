@extends('layouts.app')

@section('title', 'Simulador de Aposentadoria')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('simulators.index') }}" class="text-gray-500 hover:text-primary-600">Simuladores</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Aposentadoria</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="retirementSimulator()">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-umbrella-beach text-purple-500 mr-2"></i> Simulador de Aposentadoria
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Descubra quanto precisa poupar para se aposentar com tranquilidade.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sua Idade Atual</label>
                <input type="number" x-model.number="currentAge" min="18" max="70" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Idade para Aposentar</label>
                <input type="number" x-model.number="retirementAge" min="40" max="80" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Renda Mensal Desejada na Aposentadoria (R$)</label>
                <input type="number" x-model.number="desiredIncome" min="1000" step="500" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Patrimônio Atual (R$)</label>
                <input type="number" x-model.number="currentSavings" min="0" step="1000" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rendimento Anual Esperado (%)</label>
                <input type="number" x-model.number="annualReturn" min="1" max="30" step="0.5" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
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
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 text-center">
                        <p class="text-sm text-purple-700 dark:text-purple-400">Patrimônio Necessário</p>
                        <p class="text-3xl font-bold text-purple-600" x-text="formatCurrency(result.requiredAmount)"></p>
                    </div>

                    <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4 text-center">
                        <p class="text-sm text-primary-700 dark:text-primary-400">Aporte Mensal Necessário</p>
                        <p class="text-2xl font-bold text-primary-600" x-text="formatCurrency(result.monthlyContribution)"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Anos até Aposentar</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="result.yearsToRetirement"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Meses de Renda</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="result.monthsOfIncome"></p>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="!result">
                <div class="text-center py-12">
                    <i class="fas fa-umbrella-beach text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">Preencha os dados para descobrir quanto precisa poupar.</p>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function retirementSimulator() {
    return {
        currentAge: 30,
        retirementAge: 60,
        desiredIncome: 5000,
        currentSavings: 10000,
        annualReturn: 10,
        result: null,

        calculate() {
            const yearsToRetirement = this.retirementAge - this.currentAge;
            const monthsToRetirement = yearsToRetirement * 12;
            const monthlyRate = this.annualReturn / 100 / 12;

            // Assume 25 years of retirement (300 months)
            const retirementYears = 25;
            const monthsOfIncome = retirementYears * 12;

            // Required amount at retirement (present value of annuity)
            const requiredAmount = this.desiredIncome * ((1 - Math.pow(1 + monthlyRate, -monthsOfIncome)) / monthlyRate);

            // Future value of current savings
            const futureValueSavings = this.currentSavings * Math.pow(1 + monthlyRate, monthsToRetirement);

            // Gap to fill
            const gap = requiredAmount - futureValueSavings;

            // Monthly contribution needed (future value of annuity formula solved for PMT)
            let monthlyContribution = 0;
            if (gap > 0 && monthsToRetirement > 0) {
                monthlyContribution = gap * monthlyRate / (Math.pow(1 + monthlyRate, monthsToRetirement) - 1);
            }

            this.result = {
                requiredAmount: requiredAmount,
                monthlyContribution: Math.max(0, monthlyContribution),
                yearsToRetirement: yearsToRetirement,
                monthsOfIncome: monthsOfIncome
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
