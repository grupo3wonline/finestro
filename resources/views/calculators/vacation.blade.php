@extends('layouts.app')

@section('title', 'Calculadora de Férias')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('calculators.index') }}" class="text-gray-500 hover:text-primary-600">Calculadoras</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Férias</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="vacationCalculator()">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-plane-departure text-teal-500 mr-2"></i> Calculadora de Férias
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Calcule o valor líquido das suas férias com 1/3 constitucional e abono pecuniário.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Salário Bruto (R$)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Salário bruto mensal registrado em carteira"></i>
                </label>
                <input type="number" x-model.number="grossSalary" min="0" step="100" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 5000">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Dias de Férias
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Quantidade de dias de férias que vai tirar"></i>
                </label>
                <select x-model.number="vacationDays" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
                    <option value="30">30 dias</option>
                    <option value="20">20 dias</option>
                    <option value="15">15 dias</option>
                    <option value="10">10 dias</option>
                </select>
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" x-model="sellVacation" id="sellVacation"
                    class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                <label for="sellVacation" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Vender Abono Pecuniário (1/3 dos dias)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Converter 1/3 dos dias de férias em dinheiro"></i>
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Número de Dependentes
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Dependentes para dedução do IRRF"></i>
                </label>
                <input type="number" x-model.number="dependents" min="0" max="20" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 0">
            </div>

            <div class="flex gap-3">
                <button @click="calculate()" class="flex-1 px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg transition">
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
                    <div class="bg-teal-50 dark:bg-teal-900/20 rounded-lg p-4 text-center border border-teal-200 dark:border-teal-800">
                        <p class="text-sm text-teal-700 dark:text-teal-400">Total Líquido a Receber</p>
                        <p class="text-3xl font-bold text-teal-600" x-text="formatCurrency(result.netTotal)"></p>
                    </div>

                    <!-- Breakdown -->
                    <div class="space-y-2">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Composição</h4>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Férias (<span x-text="vacationDays"></span> dias)</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="formatCurrency(result.vacationPay)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">1/3 Constitucional</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="formatCurrency(result.constitutionalThird)"></span>
                        </div>
                        <template x-if="result.abono > 0">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Abono Pecuniário + 1/3</span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="formatCurrency(result.abono)"></span>
                            </div>
                        </template>
                        <div class="flex justify-between text-sm border-t border-gray-200 dark:border-gray-700 pt-2">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Total Bruto</span>
                            <span class="font-bold text-gray-900 dark:text-white" x-text="formatCurrency(result.grossTotal)"></span>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div class="space-y-2">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Descontos</h4>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">INSS</span>
                            <span class="font-medium text-red-600" x-text="'- ' + formatCurrency(result.inss)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">IRRF</span>
                            <span class="font-medium text-red-600" x-text="'- ' + formatCurrency(result.irrf)"></span>
                        </div>
                        <div class="flex justify-between text-sm border-t border-gray-200 dark:border-gray-700 pt-2">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Total Descontos</span>
                            <span class="font-bold text-red-600" x-text="'- ' + formatCurrency(result.totalDeductions)"></span>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="!result">
                <div class="text-center py-12">
                    <i class="fas fa-plane-departure text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">Preencha os dados e clique em calcular.</p>
                </div>
            </template>
        </div>
    </div>

    <!-- Formula -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6" x-data="{ showFormula: false }">
        <button @click="showFormula = !showFormula" class="flex items-center justify-between w-full text-left">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-book-open text-primary-500 mr-2"></i> Como é Calculado
            </h3>
            <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="{ 'rotate-180': showFormula }"></i>
        </button>
        <div x-show="showFormula" x-transition class="mt-4 space-y-3">
            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                <li><strong>Férias</strong> = Salário ÷ 30 × Dias de férias</li>
                <li><strong>1/3 Constitucional</strong> = Férias ÷ 3</li>
                <li><strong>Abono Pecuniário</strong> = (Salário ÷ 30 × Dias vendidos) + 1/3 do abono</li>
                <li><strong>INSS</strong> = Incide sobre Férias + 1/3 (não incide sobre o abono)</li>
                <li><strong>IRRF</strong> = Incide sobre Férias + 1/3 - INSS - Dependentes (não incide sobre o abono)</li>
            </ul>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                <p class="text-xs text-yellow-800 dark:text-yellow-300">
                    <i class="fas fa-info-circle mr-1"></i>
                    O abono pecuniário (venda de férias) é isento de INSS e IRRF conforme legislação trabalhista.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function vacationCalculator() {
    return {
        grossSalary: 5000,
        vacationDays: 30,
        sellVacation: false,
        dependents: 0,
        result: null,

        calculateINSS(base) {
            let inss = 0;
            const brackets = [
                { limit: 1412.00, rate: 0.075 },
                { limit: 2666.68, rate: 0.09 },
                { limit: 4000.03, rate: 0.12 },
                { limit: 7786.02, rate: 0.14 }
            ];

            let remaining = Math.min(base, 7786.02);
            let previousLimit = 0;

            for (const bracket of brackets) {
                if (remaining <= 0) break;
                const taxable = Math.min(remaining, bracket.limit - previousLimit);
                inss += taxable * bracket.rate;
                remaining -= taxable;
                previousLimit = bracket.limit;
            }

            return inss;
        },

        calculateIRRF(base, inss, dependents) {
            const dependentDeduction = dependents * 189.59;
            const taxableBase = base - inss - dependentDeduction;

            if (taxableBase <= 2259.20) return 0;
            if (taxableBase <= 2826.65) return taxableBase * 0.075 - 169.44;
            if (taxableBase <= 3751.05) return taxableBase * 0.15 - 381.44;
            if (taxableBase <= 4664.68) return taxableBase * 0.225 - 662.77;
            return taxableBase * 0.275 - 896.00;
        },

        calculate() {
            const dailyRate = this.grossSalary / 30;

            // Vacation pay
            const vacationPay = dailyRate * this.vacationDays;
            const constitutionalThird = vacationPay / 3;

            // Abono pecuniário (1/3 of vacation days sold)
            let abono = 0;
            if (this.sellVacation) {
                const soldDays = Math.floor(this.vacationDays / 3);
                const abonoBase = dailyRate * soldDays;
                abono = abonoBase + (abonoBase / 3); // Abono + 1/3 of abono
            }

            // INSS and IRRF only on vacation + 1/3 (not on abono)
            const taxableBase = vacationPay + constitutionalThird;
            const inss = this.calculateINSS(taxableBase);
            const irrf = Math.max(0, this.calculateIRRF(taxableBase, inss, this.dependents));

            const grossTotal = vacationPay + constitutionalThird + abono;
            const totalDeductions = inss + irrf;
            const netTotal = grossTotal - totalDeductions;

            this.result = {
                vacationPay,
                constitutionalThird,
                abono,
                grossTotal,
                inss,
                irrf,
                totalDeductions,
                netTotal
            };
        },

        reset() {
            this.grossSalary = 5000;
            this.vacationDays = 30;
            this.sellVacation = false;
            this.dependents = 0;
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
