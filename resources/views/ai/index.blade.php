@extends('layouts.app')

@section('title', 'Inteligência Artificial')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Inteligência Artificial</li>
    </ol>
</nav>
@endsection

@section('content')
<div x-data="aiInsights()" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-robot text-purple-500 mr-2"></i> Inteligência Artificial
            </h1>
            <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Análises e planos financeiros gerados por IA com seus dados.</p>
        </div>
        <a href="{{ route('ai.settings') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition">
            <i class="fas fa-cog mr-2"></i> Configurar IA
        </a>
    </div>

    @if(!$hasCredentials)
    <!-- No credentials configured -->
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6 text-center">
        <i class="fas fa-key text-4xl text-yellow-500 mb-3"></i>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Configure suas credenciais de IA</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-4">Para utilizar as análises com IA, você precisa configurar suas chaves de API.</p>
        <a href="{{ route('ai.settings') }}" class="inline-flex items-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition">
            <i class="fas fa-cog mr-2"></i> Configurar Agora
        </a>
    </div>
    @else
    <!-- AI Features Grid -->
    <div class="grid md:grid-cols-3 gap-6">
        <!-- Analyze Finances -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-4">
                <i class="fas fa-chart-line text-white text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Análise Financeira</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Receba uma análise completa da sua saúde financeira com recomendações personalizadas.</p>
            <button @click="analyzeFinances()" :disabled="loading" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-magic mr-2"></i> Analisar Finanças
            </button>
        </div>

        <!-- Analyze Goals -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-4">
                <i class="fas fa-bullseye text-white text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Análise de Metas</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Avalie o progresso das suas metas e receba sugestões para alcançá-las mais rápido.</p>
            <button @click="analyzeGoals()" :disabled="loading" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-magic mr-2"></i> Analisar Metas
            </button>
        </div>

        <!-- Generate Plan -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-4">
                <i class="fas fa-lightbulb text-white text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Plano Financeiro</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Gere um plano financeiro personalizado para atingir um objetivo específico.</p>
            <button @click="showPlanForm = true" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-magic mr-2"></i> Gerar Plano
            </button>
        </div>
    </div>

    <!-- Plan Form Modal -->
    <div x-show="showPlanForm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showPlanForm = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showPlanForm = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg p-6 z-10" x-transition>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-lightbulb text-purple-500 mr-2"></i> Gerar Plano Financeiro
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Objetivo *</label>
                    <input type="text" x-model="planForm.objective" autocomplete="off" placeholder="Ex: Comprar um carro, Reserva de emergência, Viagem..." class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prazo desejado</label>
                    <input type="text" x-model="planForm.timeline" autocomplete="off" placeholder="Ex: 12 meses, 2 anos, até dezembro 2025..." class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor disponível por mês (R$)</label>
                    <input type="number" x-model="planForm.monthly_available" autocomplete="off" placeholder="Ex: 500" step="0.01" min="0" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button @click="showPlanForm = false" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">Cancelar</button>
                <button @click="generatePlan()" :disabled="loading || !planForm.objective" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-medium rounded-lg transition">
                    <i class="fas fa-magic mr-1"></i> Gerar
                </button>
            </div>
        </div>
    </div>

    <!-- AI Response -->
    <div x-show="response" x-cloak x-transition class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-robot text-purple-500 mr-2"></i> Resposta da IA
                <span x-show="provider" class="text-xs font-normal text-gray-500 ml-2" x-text="'via ' + provider"></span>
            </h3>
            <button @click="response = ''" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 prose prose-sm dark:prose-invert max-w-none" x-html="formatResponse(response)"></div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" x-cloak class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
        <div class="animate-spin w-10 h-10 border-4 border-purple-200 border-t-purple-600 rounded-full mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-400">Analisando seus dados com IA...</p>
        <p class="text-xs text-gray-400 mt-1">Isso pode levar alguns segundos</p>
    </div>

    <!-- Error State -->
    <div x-show="error" x-cloak class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
        <p class="text-red-700 dark:text-red-400 text-sm"><i class="fas fa-exclamation-circle mr-1"></i> <span x-text="error"></span></p>
    </div>
    @endif

    <!-- Tip -->
    <div class="bg-primary-50/50 dark:bg-primary-900/10 border border-primary-100 dark:border-primary-800/30 rounded-xl p-4">
        <p class="text-sm text-primary-800 dark:text-primary-300">
            <i class="fas fa-lightbulb text-yellow-500 mr-1"></i>
            <strong>Dica:</strong> A IA analisa seus dados financeiros reais (saldo, transações, metas) para gerar recomendações personalizadas. Quanto mais dados você registrar, mais precisa será a análise.
        </p>
    </div>
</div>

@push('scripts')
<script>
function aiInsights() {
    return {
        loading: false,
        response: '',
        provider: '',
        error: '',
        showPlanForm: false,
        planForm: { objective: '', timeline: '', monthly_available: '' },

        async analyzeFinances() {
            await this.makeRequest('{{ route("ai.analyze.finances") }}', {});
        },

        async analyzeGoals() {
            await this.makeRequest('{{ route("ai.analyze.goals") }}', {});
        },

        async generatePlan() {
            this.showPlanForm = false;
            await this.makeRequest('{{ route("ai.generate.plan") }}', this.planForm);
        },

        async makeRequest(url, data) {
            this.loading = true;
            this.response = '';
            this.error = '';
            this.provider = '';

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                });

                const result = await res.json();

                if (result.success) {
                    this.response = result.content;
                    this.provider = result.provider || '';
                } else {
                    this.error = result.error || 'Erro ao processar a solicitação.';
                }
            } catch (e) {
                this.error = 'Erro de conexão. Verifique sua internet e tente novamente.';
            } finally {
                this.loading = false;
            }
        },

        formatResponse(text) {
            if (!text) return '';
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/^### (.*$)/gm, '<h4 class="font-semibold text-gray-900 dark:text-white mt-4 mb-2">$1</h4>')
                .replace(/^## (.*$)/gm, '<h3 class="font-bold text-gray-900 dark:text-white mt-4 mb-2">$1</h3>')
                .replace(/^# (.*$)/gm, '<h2 class="text-lg font-bold text-gray-900 dark:text-white mt-4 mb-2">$1</h2>')
                .replace(/^\d+\. (.*$)/gm, '<li class="ml-4">$1</li>')
                .replace(/^- (.*$)/gm, '<li class="ml-4 list-disc">$1</li>')
                .replace(/\n\n/g, '</p><p class="mb-3">')
                .replace(/\n/g, '<br>');
        }
    }
}
</script>
@endpush
@endsection
