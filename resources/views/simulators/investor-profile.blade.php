@extends('layouts.app')

@section('title', 'Teste de Perfil Investidor')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('simulators.index') }}" class="text-gray-500 hover:text-primary-600">Simuladores</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Perfil Investidor</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="investorProfileQuiz()" x-cloak>
    <!-- Header -->
    <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-chart-line text-green-500 mr-2"></i> Teste de Perfil Investidor
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Descubra sua tolerância a riscos e receba sugestões de alocação.</p>
    </div>

    <!-- Quiz Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 sm:p-8">

        <!-- Progress Bar -->
        <div x-show="!showResult" class="mb-8">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Pergunta <span x-text="currentStep + 1"></span> de 8</span>
                <span class="text-sm font-medium text-green-600" x-text="Math.round(((currentStep) / 8) * 100) + '%'"></span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                <div class="bg-green-500 h-2.5 rounded-full transition-all duration-500 ease-out" :style="'width: ' + ((currentStep) / 8 * 100) + '%'"></div>
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
                                :class="answers[qIndex] === (oIndex + 1) ? 'border-green-500 bg-green-50 dark:bg-green-900/20 ring-2 ring-green-500' : 'border-gray-200 dark:border-gray-600 hover:border-green-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                                class="w-full text-left px-4 py-3 rounded-lg border transition-all duration-200 flex items-center gap-3"
                            >
                                <span
                                    :class="answers[qIndex] === (oIndex + 1) ? 'bg-green-600 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300'"
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
                            :class="answers[currentStep] ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-300 dark:bg-gray-600 text-gray-500 cursor-not-allowed'"
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
            <!-- Profile Badge -->
            <div class="text-center mb-6">
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold" :class="profile.badgeClass" x-text="profile.name"></span>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Pontuação: <span x-text="totalScore"></span> de 40</p>
            </div>

            <!-- Risk Meter -->
            <div class="mb-8">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 text-center">Nível de Risco</h3>
                <div class="relative">
                    <!-- Meter Background -->
                    <div class="flex rounded-full h-6 overflow-hidden">
                        <div class="bg-green-400 flex-1"></div>
                        <div class="bg-green-300 flex-1"></div>
                        <div class="bg-yellow-300 flex-1"></div>
                        <div class="bg-orange-400 flex-1"></div>
                        <div class="bg-red-500 flex-1"></div>
                    </div>
                    <!-- Meter Labels -->
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1 px-1">
                        <span>Conservador</span>
                        <span>Moderado</span>
                        <span>Agressivo</span>
                    </div>
                    <!-- Pointer -->
                    <div class="absolute top-0 h-6 flex items-center transition-all duration-1000" :style="'left: ' + ((totalScore - 8) / 32 * 100) + '%'">
                        <div class="w-1 h-8 bg-gray-900 dark:bg-white rounded-full -mt-1 shadow-lg"></div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6">
                <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed" x-text="profile.description"></p>
            </div>

            <!-- Recommended Allocation -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-chart-pie text-green-500 mr-2"></i> Alocação Sugerida
                </h3>
                <div class="space-y-3">
                    <template x-for="(item, index) in profile.allocation" :key="index">
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="item.name"></span>
                                <span class="text-sm font-bold" :class="item.color" x-text="item.percent + '%'"></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="h-3 rounded-full transition-all duration-1000 ease-out" :class="item.barColor" :style="'width: ' + item.percent + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Tips -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i> Dicas para seu perfil
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

            <!-- Disclaimer -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3 mb-6">
                <p class="text-xs text-yellow-800 dark:text-yellow-300 flex items-start gap-2">
                    <i class="fas fa-exclamation-circle mt-0.5 flex-shrink-0"></i>
                    <span>Este teste não constitui recomendação de investimento. Consulte um profissional certificado antes de tomar decisões financeiras. Rentabilidade passada não garante rentabilidade futura.</span>
                </p>
            </div>

            <!-- Redo Button -->
            <div class="text-center">
                <button @click="reset()" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">
                    <i class="fas fa-redo mr-2"></i> Refazer o Teste
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function investorProfileQuiz() {
    return {
        currentStep: 0,
        answers: {},
        showResult: false,
        totalScore: 0,
        profile: {},

        questions: [
            {
                text: 'Qual seu horizonte de investimento?',
                options: [
                    'Menos de 1 ano',
                    'Entre 1 e 3 anos',
                    'Entre 3 e 5 anos',
                    'Entre 5 e 10 anos',
                    'Mais de 10 anos'
                ]
            },
            {
                text: 'Se seus investimentos caíssem 20% em um mês, o que faria?',
                options: [
                    'Venderia tudo imediatamente',
                    'Venderia parte para reduzir perdas',
                    'Não faria nada, esperaria recuperar',
                    'Manteria e analisaria se vale aportar mais',
                    'Compraria mais aproveitando o preço baixo'
                ]
            },
            {
                text: 'Qual porcentagem da sua renda você pode investir?',
                options: [
                    'Menos de 5%',
                    'Entre 5% e 10%',
                    'Entre 10% e 20%',
                    'Entre 20% e 30%',
                    'Mais de 30%'
                ]
            },
            {
                text: 'Qual sua experiência com investimentos?',
                options: [
                    'Nenhuma, sou iniciante total',
                    'Conheço poupança e CDB',
                    'Invisto em renda fixa há algum tempo',
                    'Tenho experiência com renda fixa e variável',
                    'Invisto há mais de 5 anos em diversos ativos'
                ]
            },
            {
                text: 'O que você prioriza em um investimento?',
                options: [
                    'Segurança total, mesmo rendendo pouco',
                    'Segurança com algum rendimento acima da inflação',
                    'Equilíbrio entre segurança e rentabilidade',
                    'Rentabilidade alta, aceito oscilações',
                    'Máximo retorno, aceito alta volatilidade'
                ]
            },
            {
                text: 'Como você reagiria a uma perda de R$ 5.000?',
                options: [
                    'Ficaria desesperado(a) e não dormiria',
                    'Ficaria muito preocupado(a)',
                    'Ficaria incomodado(a) mas seguiria em frente',
                    'Entenderia como oscilação normal',
                    'Faz parte da estratégia, sem impacto emocional'
                ]
            },
            {
                text: 'Qual seu conhecimento sobre renda variável (ações, FIIs)?',
                options: [
                    'Nenhum, não sei como funciona',
                    'Já li algo mas nunca investi',
                    'Entendo o básico e tenho pouca exposição',
                    'Conheço bem e invisto regularmente',
                    'Acompanho diariamente e opero ativamente'
                ]
            },
            {
                text: 'Qual percentual do seu patrimônio colocaria em renda variável?',
                options: [
                    '0%, prefiro apenas renda fixa',
                    'Até 10%, só para experimentar',
                    'Entre 20% e 40%',
                    'Entre 40% e 70%',
                    'Mais de 70%'
                ]
            }
        ],

        profiles: {
            ultraconservador: {
                name: 'Ultraconservador',
                badgeClass: 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-100',
                description: 'Você prioriza a segurança total do seu capital. Não aceita riscos e prefere investimentos com rentabilidade previsível, mesmo que menor. Seu foco é preservar o patrimônio acima de tudo.',
                allocation: [
                    { name: 'Renda Fixa (Tesouro Selic, CDB)', percent: 80, barColor: 'bg-blue-500', color: 'text-blue-600' },
                    { name: 'Fundos de Renda Fixa', percent: 15, barColor: 'bg-indigo-400', color: 'text-indigo-600' },
                    { name: 'Fundos Multimercado', percent: 5, barColor: 'bg-yellow-400', color: 'text-yellow-600' },
                    { name: 'Ações', percent: 0, barColor: 'bg-red-400', color: 'text-red-600' },
                    { name: 'Cripto', percent: 0, barColor: 'bg-purple-400', color: 'text-purple-600' }
                ],
                tips: [
                    'Tesouro Selic é seu melhor amigo — segurança com liquidez diária.',
                    'CDBs de bancos grandes com garantia do FGC são excelentes opções.',
                    'Considere LCIs/LCAs para isenção de imposto de renda.',
                    'Com o tempo, estude gradualmente sobre fundos multimercado conservadores.'
                ]
            },
            conservador: {
                name: 'Conservador',
                badgeClass: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
                description: 'Você prefere segurança mas aceita um pouco de risco para obter retornos melhores que a poupança. Busca investimentos estáveis com pequenas oscilações aceitáveis.',
                allocation: [
                    { name: 'Renda Fixa (Tesouro, CDB, LCI/LCA)', percent: 65, barColor: 'bg-blue-500', color: 'text-blue-600' },
                    { name: 'Fundos Multimercado', percent: 20, barColor: 'bg-yellow-400', color: 'text-yellow-600' },
                    { name: 'Fundos Imobiliários (FIIs)', percent: 10, barColor: 'bg-emerald-400', color: 'text-emerald-600' },
                    { name: 'Ações', percent: 5, barColor: 'bg-red-400', color: 'text-red-600' },
                    { name: 'Cripto', percent: 0, barColor: 'bg-purple-400', color: 'text-purple-600' }
                ],
                tips: [
                    'Diversifique entre Tesouro IPCA+ e CDBs de diferentes prazos.',
                    'Fundos imobiliários pagam dividendos mensais isentos de IR — ideal para renda.',
                    'Comece com ações de empresas sólidas (blue chips) em pequenas proporções.',
                    'Mantenha sua reserva de emergência 100% em Tesouro Selic.'
                ]
            },
            moderado: {
                name: 'Moderado',
                badgeClass: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
                description: 'Você busca equilíbrio entre segurança e rentabilidade. Aceita oscilações de curto prazo em troca de retornos melhores no longo prazo. Diversificação é sua principal estratégia.',
                allocation: [
                    { name: 'Renda Fixa', percent: 40, barColor: 'bg-blue-500', color: 'text-blue-600' },
                    { name: 'Fundos Multimercado', percent: 20, barColor: 'bg-yellow-400', color: 'text-yellow-600' },
                    { name: 'Ações Nacionais', percent: 20, barColor: 'bg-red-400', color: 'text-red-600' },
                    { name: 'Fundos Imobiliários (FIIs)', percent: 15, barColor: 'bg-emerald-400', color: 'text-emerald-600' },
                    { name: 'Cripto / Ativos Alternativos', percent: 5, barColor: 'bg-purple-400', color: 'text-purple-600' }
                ],
                tips: [
                    'Rebalanceie sua carteira a cada 6 meses para manter as proporções.',
                    'Use Tesouro IPCA+ para proteção contra inflação no longo prazo.',
                    'Diversifique em setores diferentes na renda variável.',
                    'Considere ETFs (fundos de índice) para exposição diversificada com baixo custo.'
                ]
            },
            arrojado: {
                name: 'Arrojado',
                badgeClass: 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
                description: 'Você aceita riscos maiores em busca de retornos superiores. Entende que volatilidade faz parte do jogo e tem horizonte de longo prazo para recuperar eventuais perdas.',
                allocation: [
                    { name: 'Renda Fixa', percent: 20, barColor: 'bg-blue-500', color: 'text-blue-600' },
                    { name: 'Ações Nacionais', percent: 35, barColor: 'bg-red-400', color: 'text-red-600' },
                    { name: 'Ações Internacionais', percent: 15, barColor: 'bg-pink-400', color: 'text-pink-600' },
                    { name: 'Fundos Imobiliários (FIIs)', percent: 15, barColor: 'bg-emerald-400', color: 'text-emerald-600' },
                    { name: 'Cripto / Ativos Alternativos', percent: 15, barColor: 'bg-purple-400', color: 'text-purple-600' }
                ],
                tips: [
                    'Invista em ações de crescimento (small/mid caps) com potencial de valorização.',
                    'Diversifique internacionalmente com BDRs ou ETFs globais.',
                    'Estude análise fundamentalista para escolher empresas sólidas.',
                    'Mantenha pelo menos 6 meses de reserva fora da renda variável.'
                ]
            },
            agressivo: {
                name: 'Agressivo',
                badgeClass: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
                description: 'Você busca o máximo retorno possível e tolera alta volatilidade. Tem conhecimento avançado do mercado, horizonte longo e aceita perdas temporárias significativas em busca de ganhos expressivos.',
                allocation: [
                    { name: 'Renda Fixa', percent: 10, barColor: 'bg-blue-500', color: 'text-blue-600' },
                    { name: 'Ações Nacionais', percent: 35, barColor: 'bg-red-400', color: 'text-red-600' },
                    { name: 'Ações Internacionais', percent: 25, barColor: 'bg-pink-400', color: 'text-pink-600' },
                    { name: 'Fundos Imobiliários (FIIs)', percent: 10, barColor: 'bg-emerald-400', color: 'text-emerald-600' },
                    { name: 'Cripto / Ativos Alternativos', percent: 20, barColor: 'bg-purple-400', color: 'text-purple-600' }
                ],
                tips: [
                    'Diversifique entre setores e geografias para diluir riscos concentrados.',
                    'Considere opções e derivativos apenas se tiver experiência sólida.',
                    'Tenha disciplina — não mude a estratégia por emoção em quedas.',
                    'Estude continuamente: mercado de cripto, IPOs, venture capital e startups.'
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

            if (this.totalScore <= 13) {
                this.profile = this.profiles.ultraconservador;
            } else if (this.totalScore <= 19) {
                this.profile = this.profiles.conservador;
            } else if (this.totalScore <= 26) {
                this.profile = this.profiles.moderado;
            } else if (this.totalScore <= 33) {
                this.profile = this.profiles.arrojado;
            } else {
                this.profile = this.profiles.agressivo;
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
