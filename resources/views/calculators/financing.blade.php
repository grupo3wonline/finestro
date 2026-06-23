@extends('layouts.app')

@section('title', 'Calculadora de Financiamento')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('calculators.index') }}" class="text-gray-500 hover:text-primary-600">Calculadoras</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Financiamento</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="financingCalculator()">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-home text-red-500 mr-2"></i> Calculadora de Financiamento
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Compare financiamentos nas tabelas SAC e PRICE e descubra qual é mais vantajoso para você.</p>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Valor do Bem (R$)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Valor total do imóvel ou bem financiado"></i>
                </label>
                <input type="number" x-model.number="propertyValue" min="0" step="1000" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 400000">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Valor da Entrada (R$)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Valor que será pago como entrada"></i>
                </label>
                <input type="number" x-model.number="downPayment" min="0" step="1000" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 80000">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Taxa de Juros (% ao mês)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Taxa mensal cobrada pela instituição financeira"></i>
                </label>
                <input type="number" x-model.number="monthlyRate" min="0" max="5" step="0.01" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 0.75">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Prazo (meses)
                    <i class="fas fa-question-circle text-gray-400 ml-1" title="Número de parcelas do financiamento"></i>
                </label>
                <input type="number" x-model.number="months" min="1" max="420" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: 360">
            </div>

            <div class="flex gap-3">
                <button @click="calculate()" class="flex-1 px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition">
                    <i class="fas fa-calculator mr-2"></i> Calcular
                </button>
                <button @click="reset()" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition">
                    <i class="fas fa-eraser mr-1"></i> Limpar
                </button>
            </div>
        </div>

        <!-- SAC Results -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <span class="text-blue-600">SAC</span> - Amortização Constante
            </h3>

            <template x-if="result">
                <div class="space-y-3">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-center border border-blue-200 dark:border-blue-800">
                        <p class="text-xs text-blue-700 dark:text-blue-400">Total Pago</p>
                        <p class="text-xl font-bold text-blue-600" x-text="formatCurrency(result.sac.totalPaid)"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">1ª Parcela</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(result.sac.firstPayment)"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Última Parcela</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(result.sac.lastPayment)"></p>
                        </div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-2 text-center border border-red-200 dark:border-red-800">
                        <p class="text-xs text-red-700 dark:text-red-400">Total de Juros</p>
                        <p class="text-sm font-bold text-red-600" x-text="formatCurrency(result.sac.totalInterest)"></p>
                    </div>
                </div>
            </template>

            <template x-if="!result">
                <div class="text-center py-8">
                    <i class="fas fa-chart-bar text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Parcelas decrescentes</p>
                </div>
            </template>
        </div>

        <!-- PRICE Results -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <span class="text-purple-600">PRICE</span> - Parcela Fixa
            </h3>

            <template x-if="result">
                <div class="space-y-3">
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 text-center border border-purple-200 dark:border-purple-800">
                        <p class="text-xs text-purple-700 dark:text-purple-400">Total Pago</p>
                        <p class="text-xl font-bold text-purple-600" x-text="formatCurrency(result.price.totalPaid)"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Parcela Fixa</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(result.price.fixedPayment)"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Valor Financiado</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(result.loanAmount)"></p>
                        </div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-2 text-center border border-red-200 dark:border-red-800">
                        <p class="text-xs text-red-700 dark:text-red-400">Total de Juros</p>
                        <p class="text-sm font-bold text-red-600" x-text="formatCurrency(result.price.totalInterest)"></p>
                    </div>
                </div>
            </template>

            <template x-if="!result">
                <div class="text-center py-8">
                    <i class="fas fa-equals text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Parcelas fixas</p>
                </div>
            </template>
        </div>
    </div>

    <!-- Comparison & Savings -->
    <template x-if="result">
        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800 p-6 text-center">
            <p class="text-sm text-green-700 dark:text-green-400">Economia ao escolher SAC</p>
            <p class="text-2xl font-bold text-green-600" x-text="formatCurrency(result.savings)"></p>
            <p class="text-xs text-green-600 dark:text-green-400 mt-1">O SAC geralmente custa menos no total, mas a primeira parcela é maior.</p>
        </div>
    </template>

    <!-- Detail Tables -->
    <template x-if="result">
        <div class="grid lg:grid-cols-2 gap-6">
            <!-- SAC Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Primeiras 6 parcelas - SAC</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-2 text-gray-600 dark:text-gray-400">Mês</th>
                                <th class="text-right py-2 px-2 text-gray-600 dark:text-gray-400">Parcela</th>
                                <th class="text-right py-2 px-2 text-gray-600 dark:text-gray-400">Amortização</th>
                                <th class="text-right py-2 px-2 text-gray-600 dark:text-gray-400">Juros</th>
                                <th class="text-right py-2 px-2 text-gray-600 dark:text-gray-400">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in result.sac.table" :key="row.month">
                                <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                    <td class="py-1.5 px-2 text-gray-900 dark:text-white" x-text="row.month"></td>
                                    <td class="py-1.5 px-2 text-right text-gray-700 dark:text-gray-300" x-text="formatCurrency(row.payment)"></td>
                                    <td class="py-1.5 px-2 text-right text-gray-700 dark:text-gray-300" x-text="formatCurrency(row.amortization)"></td>
                                    <td class="py-1.5 px-2 text-right text-red-600" x-text="formatCurrency(row.interest)"></td>
                                    <td class="py-1.5 px-2 text-right font-medium text-gray-900 dark:text-white" x-text="formatCurrency(row.balance)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PRICE Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Primeiras 6 parcelas - PRICE</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-2 text-gray-600 dark:text-gray-400">Mês</th>
                                <th class="text-right py-2 px-2 text-gray-600 dark:text-gray-400">Parcela</th>
                                <th class="text-right py-2 px-2 text-gray-600 dark:text-gray-400">Amortização</th>
                                <th class="text-right py-2 px-2 text-gray-600 dark:text-gray-400">Juros</th>
                                <th class="text-right py-2 px-2 text-gray-600 dark:text-gray-400">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in result.price.table" :key="row.month">
                                <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                    <td class="py-1.5 px-2 text-gray-900 dark:text-white" x-text="row.month"></td>
                                    <td class="py-1.5 px-2 text-right text-gray-700 dark:text-gray-300" x-text="formatCurrency(row.payment)"></td>
                                    <td class="py-1.5 px-2 text-right text-gray-700 dark:text-gray-300" x-text="formatCurrency(row.amortization)"></td>
                                    <td class="py-1.5 px-2 text-right text-red-600" x-text="formatCurrency(row.interest)"></td>
                                    <td class="py-1.5 px-2 text-right font-medium text-gray-900 dark:text-white" x-text="formatCurrency(row.balance)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </template>

    <!-- Formula -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6" x-data="{ showFormula: false }">
        <button @click="showFormula = !showFormula" class="flex items-center justify-between w-full text-left">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-book-open text-primary-500 mr-2"></i> Fórmulas Utilizadas
            </h3>
            <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="{ 'rotate-180': showFormula }"></i>
        </button>
        <div x-show="showFormula" x-transition class="mt-4 space-y-4">
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-blue-600 mb-2">Tabela SAC</h4>
                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                        <li><strong>Amortização</strong> = Saldo Devedor ÷ n</li>
                        <li><strong>Juros</strong> = Saldo Devedor × i</li>
                        <li><strong>Parcela</strong> = Amortização + Juros</li>
                        <li>Parcelas decrescentes (juros diminuem)</li>
                    </ul>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-purple-600 mb-2">Tabela PRICE</h4>
                    <p class="font-mono text-xs text-gray-800 dark:text-gray-200 mb-2">
                        PMT = PV × [i(1+i)ⁿ / ((1+i)ⁿ - 1)]
                    </p>
                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                        <li><strong>PV</strong> = Valor financiado</li>
                        <li><strong>i</strong> = Taxa mensal</li>
                        <li><strong>n</strong> = Número de parcelas</li>
                        <li>Parcelas fixas (composição varia)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function financingCalculator() {
    return {
        propertyValue: 400000,
        downPayment: 80000,
        monthlyRate: 0.75,
        months: 360,
        result: null,

        calculate() {
            const loanAmount = this.propertyValue - this.downPayment;
            if (loanAmount <= 0) {
                alert('O valor da entrada deve ser menor que o valor do bem.');
                return;
            }

            const i = this.monthlyRate / 100;
            const n = this.months;

            // SAC Calculation
            const sacAmortization = loanAmount / n;
            let sacBalance = loanAmount;
            let sacTotalPaid = 0;
            let sacTable = [];

            for (let m = 1; m <= n; m++) {
                const interest = sacBalance * i;
                const payment = sacAmortization + interest;
                sacBalance -= sacAmortization;
                sacTotalPaid += payment;

                if (m <= 6) {
                    sacTable.push({
                        month: m,
                        payment,
                        amortization: sacAmortization,
                        interest,
                        balance: Math.max(0, sacBalance)
                    });
                }
            }

            const sacFirstPayment = sacAmortization + (loanAmount * i);
            const sacLastPayment = sacAmortization + (sacAmortization * i);

            // PRICE Calculation
            const pricePMT = loanAmount * (i * Math.pow(1 + i, n)) / (Math.pow(1 + i, n) - 1);
            let priceBalance = loanAmount;
            let priceTotalPaid = 0;
            let priceTable = [];

            for (let m = 1; m <= n; m++) {
                const interest = priceBalance * i;
                const amortization = pricePMT - interest;
                priceBalance -= amortization;
                priceTotalPaid += pricePMT;

                if (m <= 6) {
                    priceTable.push({
                        month: m,
                        payment: pricePMT,
                        amortization,
                        interest,
                        balance: Math.max(0, priceBalance)
                    });
                }
            }

            const savings = priceTotalPaid - sacTotalPaid;

            this.result = {
                loanAmount,
                sac: {
                    firstPayment: sacFirstPayment,
                    lastPayment: sacLastPayment,
                    totalPaid: sacTotalPaid,
                    totalInterest: sacTotalPaid - loanAmount,
                    table: sacTable
                },
                price: {
                    fixedPayment: pricePMT,
                    totalPaid: priceTotalPaid,
                    totalInterest: priceTotalPaid - loanAmount,
                    table: priceTable
                },
                savings
            };
        },

        reset() {
            this.propertyValue = 400000;
            this.downPayment = 80000;
            this.monthlyRate = 0.75;
            this.months = 360;
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
