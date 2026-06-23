@extends('layouts.app')

@section('title', 'Calculadora de 13º Salário')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('calculators.index') }}" class="text-gray-500 hover:text-primary-600">Calculadoras</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">13º Salário</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="thirteenthSalaryCalculator()">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-money-bill-wave text-orange-500 mr-2"></i> Calculadora de 13º Salário
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Calcule as parcelas do décimo terceiro com descontos de INSS e IRRF (tabelas 2024).</p>
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
                    Meses Trabalhados no Ano
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Quantos meses trabalhou neste ano (1 a 12)"></i>
                </label>
                <input type="number" x-model.number="monthsWorked" min="1" max="12" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 12">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Número de Dependentes
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Dependentes para dedução do IRRF (R$ 189,59 cada)"></i>
                </label>
                <input type="number" x-model.number="dependents" min="0" max="20" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 0">
            </div>

            <div class="flex gap-3">
                <button @click="calculate()" class="flex-1 px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition">
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
                    <!-- 1st Installment -->
                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800">
                        <p class="text-sm font-medium text-orange-700 dark:text-orange-400 mb-2">1ª Parcela (até 30/nov)</p>
                        <p class="text-2xl font-bold text-orange-600" x-text="formatCurrency(result.firstInstallment)"></p>
                        <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">Sem descontos</p>
                    </div>

                    <!-- 2nd Installment -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                        <p class="text-sm font-medium text-blue-700 dark:text-blue-400 mb-2">2ª Parcela (até 20/dez)</p>
                        <p class="text-2xl font-bold text-blue-600" x-text="formatCurrency(result.secondInstallment)"></p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Com descontos de INSS e IRRF</p>
                    </div>

                    <!-- Details -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Detalhamento</h4>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">13º Bruto Proporcional</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="formatCurrency(result.grossThirteenth)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">INSS</span>
                            <span class="font-medium text-red-600" x-text="'- ' + formatCurrency(result.inss)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">IRRF</span>
                            <span class="font-medium text-red-600" x-text="'- ' + formatCurrency(result.irrf)"></span>
                        </div>
                        <div class="flex justify-between text-sm border-t border-gray-200 dark:border-gray-700 pt-2">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Total Líquido</span>
                            <span class="font-bold text-green-600" x-text="formatCurrency(result.netTotal)"></span>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="!result">
                <div class="text-center py-12">
                    <i class="fas fa-money-bill-wave text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">Preencha os dados e clique em calcular.</p>
                </div>
            </template>
        </div>
    </div>

    <!-- Tax Tables -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6" x-data="{ showFormula: false }">
        <button @click="showFormula = !showFormula" class="flex items-center justify-between w-full text-left">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-book-open text-primary-500 mr-2"></i> Tabelas de INSS e IRRF (2024)
            </h3>
            <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="{ 'rotate-180': showFormula }"></i>
        </button>
        <div x-show="showFormula" x-transition class="mt-4 space-y-4">
            <!-- INSS Table -->
            <div>
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Faixas INSS 2024 (Progressiva)</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-3 text-gray-600 dark:text-gray-400">Faixa Salarial</th>
                                <th class="text-right py-2 px-3 text-gray-600 dark:text-gray-400">Alíquota</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 dark:text-gray-300">
                            <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                <td class="py-2 px-3">Até R$ 1.412,00</td>
                                <td class="py-2 px-3 text-right">7,5%</td>
                            </tr>
                            <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                <td class="py-2 px-3">R$ 1.412,01 a R$ 2.666,68</td>
                                <td class="py-2 px-3 text-right">9%</td>
                            </tr>
                            <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                <td class="py-2 px-3">R$ 2.666,69 a R$ 4.000,03</td>
                                <td class="py-2 px-3 text-right">12%</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3">R$ 4.000,04 a R$ 7.786,02</td>
                                <td class="py-2 px-3 text-right">14%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- IRRF Table -->
            <div>
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Faixas IRRF 2024</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-3 text-gray-600 dark:text-gray-400">Base de Cálculo</th>
                                <th class="text-right py-2 px-3 text-gray-600 dark:text-gray-400">Alíquota</th>
                                <th class="text-right py-2 px-3 text-gray-600 dark:text-gray-400">Dedução</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 dark:text-gray-300">
                            <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                <td class="py-2 px-3">Até R$ 2.259,20</td>
                                <td class="py-2 px-3 text-right">Isento</td>
                                <td class="py-2 px-3 text-right">—</td>
                            </tr>
                            <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                <td class="py-2 px-3">R$ 2.259,21 a R$ 2.826,65</td>
                                <td class="py-2 px-3 text-right">7,5%</td>
                                <td class="py-2 px-3 text-right">R$ 169,44</td>
                            </tr>
                            <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                <td class="py-2 px-3">R$ 2.826,66 a R$ 3.751,05</td>
                                <td class="py-2 px-3 text-right">15%</td>
                                <td class="py-2 px-3 text-right">R$ 381,44</td>
                            </tr>
                            <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                <td class="py-2 px-3">R$ 3.751,06 a R$ 4.664,68</td>
                                <td class="py-2 px-3 text-right">22,5%</td>
                                <td class="py-2 px-3 text-right">R$ 662,77</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3">Acima de R$ 4.664,68</td>
                                <td class="py-2 px-3 text-right">27,5%</td>
                                <td class="py-2 px-3 text-right">R$ 896,00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Dedução por dependente: R$ 189,59</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function thirteenthSalaryCalculator() {
    return {
        grossSalary: 5000,
        monthsWorked: 12,
        dependents: 0,
        result: null,

        calculateINSS(base) {
            // Progressive INSS 2024
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
            // Base = gross - INSS - dependents deduction
            const dependentDeduction = dependents * 189.59;
            const taxableBase = base - inss - dependentDeduction;

            if (taxableBase <= 2259.20) return 0;
            if (taxableBase <= 2826.65) return taxableBase * 0.075 - 169.44;
            if (taxableBase <= 3751.05) return taxableBase * 0.15 - 381.44;
            if (taxableBase <= 4664.68) return taxableBase * 0.225 - 662.77;
            return taxableBase * 0.275 - 896.00;
        },

        calculate() {
            const grossThirteenth = (this.grossSalary * this.monthsWorked) / 12;
            const firstInstallment = grossThirteenth / 2;

            // Deductions apply on the full 13th amount
            const inss = this.calculateINSS(grossThirteenth);
            const irrf = Math.max(0, this.calculateIRRF(grossThirteenth, inss, this.dependents));

            const secondInstallment = grossThirteenth - firstInstallment - inss - irrf;
            const netTotal = firstInstallment + secondInstallment;

            this.result = {
                grossThirteenth,
                firstInstallment,
                secondInstallment,
                inss,
                irrf,
                netTotal
            };
        },

        reset() {
            this.grossSalary = 5000;
            this.monthsWorked = 12;
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
