@extends('layouts.app')

@section('title', 'Teste de Perfil Financeiro')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('simulators.index') }}" class="text-gray-500 hover:text-primary-600">Simuladores</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Perfil Financeiro</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="financialProfileQuiz()" x-cloak>
    <!-- Header -->
    <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-user-circle text-primary-500 mr-2"></i> Teste de Perfil Financeiro
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Descubra como você lida com suas finanças e receba dicas personalizadas.</p>
    </div>

    <!-- Quiz Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 sm:p-8">

        <!-- Progress Bar -->
        <div x-show="!showResult" class="mb-8">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Pergunta <span x-text="currentStep + 1"></span> de 8</span>
                <span class="text-sm font-medium text-primary-600" x-text="Math.round(((currentStep) / 8) * 100) + '%'"></span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                <div class="bg-primary-600 h-2.5 rounded-full transition-all duration-500 ease-out" :style="'width: ' + ((currentStep) / 8 * 100) + '%'"></div>
            </div>
        </div>

        <!-- Questions -->
        <div x-show="!showResult">
            <template x-for="(question, qIndex) in questions" :key="qIndex">
                <div x-show="currentStep === qIndex" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6" x-text="question.text"></h2>

                    <div class="space-y-3">
                        <template x-for="(option, oIndex) in question.options" :key="oIndex">
                            <button
                                @click="selectAnswer(qIndex, oIndex + 1)"
                                :class="answers[qIndex] === (oIndex + 1) ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-2 ring-primary-500' : 'border-gray-200 dark:border-gray-600 hover:border-primary-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                                class="w-full text-left px-4 py-3 rounded-lg border transition-all duration-200 flex items-center gap-3"
                            >
                                <span
                                    :class="answers[qIndex] === (oIndex + 1) ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300'"
                                    class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-medium flex-shrink-0 transition-colors"
                                    x-text="String.fromCharCode(65 + oIndex)">
                                </span>
                                <span class="text-gray-700 dark:text-gray-200 text-sm" x-text="option"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Navigation -->
                    <div class="flex justify-between items-center mt-8">
                        <button
                            @click="prevStep()"
                            x-show="currentStep > 0"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium rounded-lg transition flex items-center gap-2"
                        >
                            <i class="fas fa-arrow-left"></i> Anterior
                        </button>
                        <div x-show="currentStep === 0"></div>

                        <button
                            @click="nextStep()"
                            :disabled="!answers[currentStep]"
                            :class="answers[currentStep] ? 'bg-primary-600 hover:bg-primary-700 text-white' : 'bg-gray-300 dark:bg-gray-600 text-gray-500 cursor-not-allowed'"
                            class="px-6 py-2 font-medium rounded-lg transition flex items-center gap-2"
                        >
                            <span x-text="currentStep === 7 ? 'Ver Resultado' : 'Próxima'"></span>
                            <i class="fas" :class="currentStep === 7 ? 'fa-check' : 'fa-arrow-right'"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Result -->
        <div x-show="showResult" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            <div class="text-center mb-6">
                <div class="w-20 h-20 rounded-full mx-auto flex items-center justify-center mb-4" :class="profile.bgColor">
                    <i class="text-3xl" :class="profile.icon + ' ' + profile.textColor"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white" x-text="'Seu perfil: ' + profile.name"></h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pontuação: <span x-text="totalScore"></span> de 40</p>
            </div>

            <!-- Score Bar -->
            <div class="mb-6">
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span>Endividado</span>
                    <span>Investidor</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 relative overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-1000 ease-out" :class="profile.barColor" :style="'width: ' + ((totalScore - 8) / 32 * 100) + '%'"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-400 mt-1">
                    <span>8</span>
                    <span>16</span>
                    <span>24</span>
                    <span>32</span>
                    <span>40</span>
                </div>
            </div>

            <!-- Description -->
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6">
                <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed" x-text="profile.description"></p>
            </div>

            <!-- Tips -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i> Dicas para você
                </h3>
                <ul class="space-y-2">
                    <template x-for="(tip, index) in profile.tips" :key="index">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
                            <span class="text-sm text-gray-700 dark:text-gray-300" x-text="tip"></span>
                        </li>
                    </template>
                </ul>
            </div>

            <!-- Redo Button -->
            <div class="text-center">
                <button @click="reset()" class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition">
                    <i class="fas fa-redo mr-2"></i> Refazer o Teste
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function financialProfileQuiz() {
    return {
        currentStep: 0,
        answers: {},
        showResult: false,
        totalScore: 0,
        profile: {},

        questions: [
            {
                text: 'Como você se sente em relação às suas finanças hoje?',
                options: [
                    'Muito estressado(a), não sei como pagar as contas',
                    'Preocupado(a), as contas apertam todo mês',
                    'Mais ou menos, dou conta mas sem folga',
                    'Tranquilo(a), consigo me organizar bem',
                    'Muito tranquilo(a), tenho total controle'
                ]
            },
            {
                text: 'Você sabe exatamente quanto ganha e quanto gasta por mês?',
                options: [
                    'Não faço ideia dos meus gastos',
                    'Tenho uma noção vaga',
                    'Sei mais ou menos, mas não anoto',
                    'Acompanho a maioria dos gastos',
                    'Sim, controlo tudo detalhadamente'
                ]
            },
            {
                text: 'Você tem uma reserva de emergência?',
                options: [
                    'Não tenho nada guardado',
                    'Tenho menos de 1 mês de despesas',
                    'Tenho entre 1 e 3 meses',
                    'Tenho entre 3 e 6 meses',
                    'Tenho mais de 12 meses de reserva'
                ]
            },
            {
                text: 'Como você lida com dívidas?',
                options: [
                    'Tenho muitas dívidas e não consigo pagar',
                    'Tenho dívidas mas pago o mínimo',
                    'Tenho poucas dívidas, tudo sob controle',
                    'Só tenho financiamento planejado',
                    'Não tenho nenhuma dívida'
                ]
            },
            {
                text: 'Você costuma planejar grandes compras?',
                options: [
                    'Compro por impulso, sem pensar',
                    'Às vezes compro sem planejar',
                    'Penso um pouco antes de comprar',
                    'Pesquiso preços e espero promoções',
                    'Sempre pesquiso e planejo com antecedência'
                ]
            },
            {
                text: 'Qual a sua relação com investimentos?',
                options: [
                    'Nunca investi na vida',
                    'Já ouvi falar mas nunca fiz',
                    'Tenho poupança ou algo simples',
                    'Invisto regularmente em renda fixa',
                    'Tenho carteira diversificada com vários ativos'
                ]
            },
            {
                text: 'Você tem metas financeiras definidas?',
                options: [
                    'Não penso nisso, vivo o dia a dia',
                    'Tenho vontades mas nada concreto',
                    'Tenho algumas metas vagas',
                    'Tenho metas definidas mas sem prazo',
                    'Tenho metas claras com prazos e valores'
                ]
            },
            {
                text: 'Como é sua relação com cartão de crédito?',
                options: [
                    'Sempre estouro o limite e parcelo tudo',
                    'Uso bastante e às vezes perco o controle',
                    'Uso com moderação, pago a fatura em dia',
                    'Uso de forma consciente e acompanho os gastos',
                    'Uso como ferramenta estratégica para pontos e cashback'
                ]
            }
        ],

        profiles: {
            endividado: {
                name: 'Endividado',
                icon: 'fas fa-exclamation-triangle',
                bgColor: 'bg-red-100 dark:bg-red-900/30',
                textColor: 'text-red-600 dark:text-red-400',
                barColor: 'bg-red-500',
                description: 'Você está passando por dificuldades financeiras. Seus gastos superam sua renda e as dívidas estão acumulando. O primeiro passo é reconhecer a situação — e você já fez isso ao completar este teste. Com organização e disciplina, é possível reverter esse cenário.',
                tips: [
                    'Liste todas as suas dívidas com valores e juros — priorize pagar as de maior taxa.',
                    'Corte gastos não essenciais imediatamente (assinaturas, delivery, compras por impulso).',
                    'Tente renegociar dívidas diretamente com os credores ou em feirões de negociação.',
                    'Estabeleça um orçamento mínimo e acompanhe cada gasto diariamente.'
                ]
            },
            desorganizado: {
                name: 'Desorganizado',
                icon: 'fas fa-random',
                bgColor: 'bg-orange-100 dark:bg-orange-900/30',
                textColor: 'text-orange-600 dark:text-orange-400',
                barColor: 'bg-orange-500',
                description: 'Você não tem grandes dívidas, mas também não planeja suas finanças. Vive no automático, sem saber exatamente para onde vai o dinheiro. Com pequenas mudanças de hábito, você pode ganhar muito mais controle e tranquilidade.',
                tips: [
                    'Comece anotando todos os seus gastos por pelo menos 30 dias.',
                    'Separe suas despesas em categorias (fixas, variáveis, lazer) para entender seu padrão.',
                    'Defina um limite mensal para gastos variáveis e respeite esse teto.',
                    'Use um aplicativo de controle financeiro para automatizar o acompanhamento.'
                ]
            },
            equilibrado: {
                name: 'Equilibrado',
                icon: 'fas fa-balance-scale',
                bgColor: 'bg-blue-100 dark:bg-blue-900/30',
                textColor: 'text-blue-600 dark:text-blue-400',
                barColor: 'bg-blue-500',
                description: 'Você tem as contas em dia e controla razoavelmente seus gastos, mas ainda não investe de forma consistente. Está no caminho certo — o próximo passo é fazer seu dinheiro trabalhar para você.',
                tips: [
                    'Monte uma reserva de emergência equivalente a 6 meses de despesas.',
                    'Comece a investir, mesmo que pouco — o hábito é mais importante que o valor.',
                    'Defina metas financeiras com prazos específicos (viagem, curso, entrada de imóvel).',
                    'Automatize investimentos: configure transferência automática no dia do pagamento.'
                ]
            },
            poupador: {
                name: 'Poupador',
                icon: 'fas fa-piggy-bank',
                bgColor: 'bg-emerald-100 dark:bg-emerald-900/30',
                textColor: 'text-emerald-600 dark:text-emerald-400',
                barColor: 'bg-emerald-500',
                description: 'Você tem disciplina financeira e guarda dinheiro regularmente. Possui reserva de emergência e controla bem seus gastos. O próximo nível é diversificar investimentos e buscar rentabilidades maiores.',
                tips: [
                    'Diversifique: além da renda fixa, considere fundos multimercado e renda variável.',
                    'Estude sobre investimentos — conhecimento financeiro é seu maior ativo.',
                    'Revise sua carteira periodicamente e rebalanceie conforme suas metas.',
                    'Considere investir em sua educação financeira (cursos, livros, mentorias).'
                ]
            },
            investidor: {
                name: 'Investidor',
                icon: 'fas fa-chart-pie',
                bgColor: 'bg-purple-100 dark:bg-purple-900/30',
                textColor: 'text-purple-600 dark:text-purple-400',
                barColor: 'bg-purple-500',
                description: 'Parabéns! Você faz o dinheiro trabalhar para você. Tem carteira diversificada, metas claras e usa o crédito de forma estratégica. Continue evoluindo e compartilhe seu conhecimento.',
                tips: [
                    'Explore investimentos internacionais para diversificação geográfica.',
                    'Considere criar fontes de renda passiva (dividendos, FIIs, aluguel).',
                    'Planeje a longo prazo: aposentadoria, herança, independência financeira.',
                    'Compartilhe seu conhecimento — ensinar consolida o aprendizado.'
                ]
            }
        },

        selectAnswer(questionIndex, value) {
            this.answers[questionIndex] = value;
        },

        nextStep() {
            if (!this.answers[this.currentStep]) return;

            if (this.currentStep < 7) {
                this.currentStep++;
            } else {
                this.calculateResult();
            }
        },

        prevStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
            }
        },

        calculateResult() {
            this.totalScore = Object.values(this.answers).reduce((sum, val) => sum + val, 0);

            if (this.totalScore <= 14) {
                this.profile = this.profiles.endividado;
            } else if (this.totalScore <= 20) {
                this.profile = this.profiles.desorganizado;
            } else if (this.totalScore <= 27) {
                this.profile = this.profiles.equilibrado;
            } else if (this.totalScore <= 33) {
                this.profile = this.profiles.poupador;
            } else {
                this.profile = this.profiles.investidor;
            }

            this.showResult = true;
        },

        reset() {
            this.currentStep = 0;
            this.answers = {};
            this.showResult = false;
            this.totalScore = 0;
            this.profile = {};
        }
    }
}
</script>
@endpush
@endsection
