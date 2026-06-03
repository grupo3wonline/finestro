@extends('layouts.app')

@section('title', 'Simulador de Orçamento')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('simulators.index') }}" class="text-gray-500 hover:text-primary-600">Simuladores</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Orçamento</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="budgetSimulator()">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-calculator text-orange-500 mr-2"></i> Simulador de Orçamento (50-30-20)
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Distribua sua renda usando a regra 50-30-20: 50% necessidades, 30% desejos, 20% poupança.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Renda Mensal Líquida (R$)</label>
                <input type="number" x-model.number="income" min="0" step="100" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="5000">
            </div>

            <button @click="calculate()" class="w-full px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition">
                <i class="fas fa-calculator mr-2"></i> Calcular Distribuição
            </button>

            <!-- Info -->
            <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                <h4 class="font-medium text-orange-800 dark:text-orange-300 text-sm mb-2">Como funciona a regra 50-30-20?</h4>
                <ul class="text-xs text-orange-700 dark:text-orange-400 space-y-1">
                    <li><strong>50% Necessidades:</strong> Moradia, alimentação, transporte, saúde, contas fixas</li>
                    <li><strong>30% Desejos:</strong> Lazer, restaurantes, streaming, compras não essenciais</li>
                    <li><strong>20% Poupança:</strong> Investimentos, reserva de emergência, metas financeiras</li>
                </ul>
            </div>
        </div>

        <!-- Results -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Distribuição do Orçamento</h3>

            <template x-if="result">
                <div class="space-y-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-blue-800 dark:text-blue-300"><i class="fas fa-home mr-1"></i> Necessidades (50%)</span>
                            <span class="font-bold text-blue-600" x-text="formatCurrency(result.needs)"></span>
                        </div>
                        <div class="w-full bg-blue-200 dark:bg-blue-800 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: 50%"></div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-yellow-800 dark:text-yellow-300"><i class="fas fa-smile mr-1"></i> Desejos (30%)</span>
                            <span class="font-bold text-yellow-600" x-text="formatCurrency(result.wants)"></span>
                        </div>
                        <div class="w-full bg-yellow-200 dark:bg-yellow-800 rounded-full h-2">
                            <div class="bg-yellow-500 h-2 rounded-full" style="width: 30%"></div>
                        </div>
                    </div>

                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-green-800 dark:text-green-300"><i class="fas fa-piggy-bank mr-1"></i> Poupança (20%)</span>
                            <span class="font-bold text-green-600" x-text="formatCurrency(result.savings)"></span>
                        </div>
                        <div class="w-full bg-green-200 dark:bg-green-800 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: 20%"></div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                            Poupando <span class="font-semibold text-green-600" x-text="formatCurrency(result.savings)"></span>/mês,
                            em 12 meses você terá <span class="font-semibold text-green-600" x-text="formatCurrency(result.savings * 12)"></span>
                        </p>
                    </div>
                </div>
            </template>

            <template x-if="!result">
                <div class="text-center py-12">
                    <i class="fas fa-calculator text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">Informe sua renda para ver a distribuição ideal.</p>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function budgetSimulator() {
    return {
        income: 5000,
        result: null,

        calculate() {
            this.result = {
                needs: this.income * 0.50,
                wants: this.income * 0.30,
                savings: this.income * 0.20
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
