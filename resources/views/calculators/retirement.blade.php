@extends('layouts.app')

@section('title', 'Calculadora de Aposentadoria')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('calculators.index') }}" class="text-gray-500 hover:text-primary-600">Calculadoras</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Aposentadoria</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="retirementCalculator()">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-umbrella-beach text-purple-500 mr-2"></i> Calculadora de Aposentadoria
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Descubra quanto precisa investir por mês para se aposentar com a renda desejada.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Idade Atual
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Sua idade hoje"></i>
                </label>
                <input type="number" x-model.number="currentAge" min="16" max="80" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 30">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Idade Desejada de Aposentadoria
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Com quantos anos deseja se aposentar"></i>
                </label>
                <input type="number" x-model.number="retirementAge" min="30" max="100" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 60">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Renda Mensal Desejada na Aposentadoria (R$)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Quanto quer receber por mês na aposentadoria"></i>
                </label>
                <input type="number" x-model.number="desiredIncome" min="0" step="500" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 10000">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Patrimônio Atual (R$)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Quanto você já tem investido hoje"></i>
                </label>
                <input type="number" x-model.number="currentWealth" min="0" step="1000" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 50000">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Rentabilidade Esperada (% ao ano)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Rendimento anual esperado dos investimentos (real, acima da inflação)"></i>
                </label>
                <input type="number" x-model.number="annualReturn" min="0" max="30" step="0.5" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 8">
            </div>

            <div class="flex gap-3">
                <button @click="calculate()" class="flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition">
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
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 text-center border border-purple-200 dark:border-purple-800">
                        <p class="text-sm text-purple-700 dark:text-purple-400">Patrimônio Necessário</p>
                        <p class="text-3xl font-bold text-purple-600" x-text="formatCurrency(result.requiredWealth)"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Anos até Aposentadoria</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="result.yearsToRetirement"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Meses até Aposentadoria</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="result.monthsToRetirement"></p>
                        </div>
                    </div>

                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center border border-green-200 dark:border-green-800">
                        <p class="text-sm text-green-700 dark:text-green-400">Investimento Mensal Necessário</p>
                        <p class="text-3xl font-bold text-green-600" x-text="formatCurrency(result.monthlyInvestment)"></p>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-center border border-blue-200 dark:border-blue-800">
                        <p class="text-xs text-blue-700 dark:text-blue-400">Quanto falta acumular</p>
                        <p class="text-lg font-semibold text-blue-600" x-text="formatCurrency(result.gap)"></p>
                    </div>
                </div>
            </template>

            <template x-if="!result">
                <div class="text-center py-12">
                    <i class="fas fa-umbrella-beach text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">Preencha os dados e clique em calcular.</p>
                </div>
            </template>
        </div>
    </div>

    <!-- Formula -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6" x-data="{ showFormula: false }">
        <button @click="showFormula = !showFormula" class="flex items-center justify-between w-full text-left">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-book-open text-primary-500 mr-2"></i> Metodologia Utilizada
            </h3>
            <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="{ 'rotate-180': showFormula }"></i>
        </button>
        <div x-show="showFormula" x-transition class="mt-4 space-y-3">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 space-y-2">
                <p class="font-mono text-sm text-gray-800 dark:text-gray-200">
                    <strong>Regra dos 4%:</strong> Patrimônio = Renda Mensal × 12 / 0,04
                </p>
                <p class="font-mono text-sm text-gray-800 dark:text-gray-200">
                    <strong>Aporte Mensal:</strong> PMT = (VF - PV × (1+i)ⁿ) × i / ((1+i)ⁿ - 1)
                </p>
            </div>
            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                <li><strong>Regra dos 4%</strong> — metodologia FIRE: você pode retirar 4% ao ano do patrimônio sem consumi-lo</li>
                <li><strong>VF</strong> = Valor futuro necessário (patrimônio alvo)</li>
                <li><strong>PV</strong> = Patrimônio atual</li>
                <li><strong>i</strong> = Taxa mensal de rentabilidade</li>
                <li><strong>n</strong> = Meses até a aposentadoria</li>
            </ul>
        </div>
    </div>
</div>

@push('scripts')
<script>
function retirementCalculator() {
    return {
        currentAge: 30,
        retirementAge: 60,
        desiredIncome: 10000,
        currentWealth: 50000,
        annualReturn: 8,
        result: null,

        calculate() {
            const yearsToRetirement = this.retirementAge - this.currentAge;
            if (yearsToRetirement <= 0) {
                alert('A idade de aposentadoria deve ser maior que a idade atual.');
                return;
            }

            const monthsToRetirement = yearsToRetirement * 12;
            const monthlyRate = Math.pow(1 + (this.annualReturn / 100), 1/12) - 1;

            // 4% rule: required wealth
            const requiredWealth = (this.desiredIncome * 12) / 0.04;

            // Future value of current wealth
            const futureCurrentWealth = this.currentWealth * Math.pow(1 + monthlyRate, monthsToRetirement);

            // Gap to fill
            const gap = Math.max(0, requiredWealth - futureCurrentWealth);

            // Monthly investment needed (PMT for future value of annuity)
            let monthlyInvestment = 0;
            if (gap > 0 && monthlyRate > 0) {
                monthlyInvestment = gap * monthlyRate / (Math.pow(1 + monthlyRate, monthsToRetirement) - 1);
            } else if (gap > 0) {
                monthlyInvestment = gap / monthsToRetirement;
            }

            this.result = {
                requiredWealth,
                yearsToRetirement,
                monthsToRetirement,
                monthlyInvestment,
                gap
            };
        },

        reset() {
            this.currentAge = 30;
            this.retirementAge = 60;
            this.desiredIncome = 10000;
            this.currentWealth = 50000;
            this.annualReturn = 8;
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
