@extends('layouts.landing')

@section('title', 'Ferramentas')
@section('meta_description', 'Ferramentas financeiras do Finestro: 7 calculadoras gratuitas, 3 simuladores, testes de perfil, cotação de moedas em tempo real e 5 funcionalidades de IA.')

@section('content')
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8 text-sm" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
                <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
                <li class="text-gray-700 dark:text-gray-300 font-medium">Ferramentas</li>
            </ol>
        </nav>
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">Ferramentas Financeiras</h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Calculadoras, simuladores, testes e inteligência artificial para decisões financeiras mais inteligentes.
            </p>
        </div>
    </div>
</section>

<!-- Calculadoras Section -->
<section class="py-16 bg-white dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-calculator text-teal-600"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Calculadoras Financeiras</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">7 calculadoras gratuitas para todos os usuários</p>
            </div>
            <span class="ml-auto inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                <i class="fas fa-check-circle mr-1"></i> Grátis
            </span>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-chart-line text-primary-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Investimento</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Calcule rendimentos com juros compostos, aportes mensais e diferentes prazos.</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-percentage text-orange-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Juros</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Compare juros simples e compostos. Calcule o custo real de empréstimos.</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-umbrella-beach text-secondary-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Aposentadoria</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Descubra quanto poupar por mês para se aposentar com tranquilidade.</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-gift text-red-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">13º Salário</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Calcule o valor líquido do seu 13º considerando descontos e parcelas.</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-plane text-blue-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Férias</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Calcule o valor das suas férias com abono, descontos e adiantamento do 13º.</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-home text-purple-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Financiamento</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Simule parcelas de financiamento imobiliário ou veicular com diferentes taxas.</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-coins text-amber-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Cotação de Moedas</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Converta valores entre moedas com cotações atualizadas em tempo real.</p>
            </div>
        </div>
    </div>
</section>

<!-- Simuladores Section -->
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-flask text-orange-600"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Simuladores Financeiros</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">3 simuladores avançados com cenários personalizados</p>
            </div>
            <span class="ml-auto inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400">
                <i class="fas fa-crown mr-1"></i> Pro
            </span>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-chart-area text-primary-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Simulador de Investimentos</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Projete o crescimento do seu patrimônio com diferentes cenários de aportes, taxas e prazos.</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-umbrella-beach text-secondary-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Simulador de Aposentadoria</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Planeje sua aposentadoria com projeções detalhadas de renda, despesas e patrimônio acumulado.</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-balance-scale text-purple-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Simulador de Orçamento</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Monte orçamentos mensais com distribuição inteligente entre necessidades, desejos e investimentos.</p>
            </div>
        </div>
    </div>
</section>

<!-- Testes de Perfil Section -->
<section class="py-16 bg-white dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-check text-indigo-600"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Testes de Perfil</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Descubra seu perfil financeiro e de investidor</p>
            </div>
            <span class="ml-auto inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                <i class="fas fa-check-circle mr-1"></i> Grátis
            </span>
        </div>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-brain text-indigo-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Teste de Perfil Financeiro</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Avalie seus hábitos financeiros e descubra se você é poupador, equilibrado ou gastador. Receba orientações personalizadas.</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-chart-bar text-teal-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Teste de Perfil Investidor</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Identifique se você é conservador, moderado ou arrojado. Descubra investimentos adequados ao seu perfil de risco.</p>
            </div>
        </div>
    </div>
</section>

<!-- Cotação de Moedas Section -->
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-coins text-amber-600"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Cotação de Moedas</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Acompanhe 9 moedas em tempo real</p>
            </div>
            <span class="ml-auto inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                <i class="fas fa-check-circle mr-1"></i> Grátis
            </span>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="grid md:grid-cols-3 gap-6">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Moedas Disponíveis</h3>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li><i class="fas fa-dollar-sign text-green-500 mr-2"></i>Dólar Americano (USD)</li>
                        <li><i class="fas fa-euro-sign text-blue-500 mr-2"></i>Euro (EUR)</li>
                        <li><i class="fas fa-pound-sign text-purple-500 mr-2"></i>Libra Esterlina (GBP)</li>
                    </ul>
                </div>
                <div>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400 md:mt-8">
                        <li><i class="fas fa-yen-sign text-red-500 mr-2"></i>Iene Japonês (JPY)</li>
                        <li><i class="fas fa-dollar-sign text-teal-500 mr-2"></i>Dólar Canadense (CAD)</li>
                        <li><i class="fas fa-dollar-sign text-orange-500 mr-2"></i>Dólar Australiano (AUD)</li>
                    </ul>
                </div>
                <div>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400 md:mt-8">
                        <li><i class="fas fa-money-bill text-gray-500 mr-2"></i>Franco Suíço (CHF)</li>
                        <li><i class="fas fa-yen-sign text-indigo-500 mr-2"></i>Yuan Chinês (CNY)</li>
                        <li><i class="fas fa-money-bill text-amber-500 mr-2"></i>Peso Argentino (ARS)</li>
                    </ul>
                </div>
            </div>
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                <i class="fas fa-sync-alt mr-1"></i> Cotações atualizadas automaticamente em tempo real.
            </p>
        </div>
    </div>
</section>

<!-- Inteligência Artificial Section -->
<section class="py-16 bg-white dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-violet-100 dark:bg-violet-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-robot text-violet-600"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Inteligência Artificial</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">5 funcionalidades de IA para análises avançadas</p>
            </div>
            <span class="ml-auto inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400">
                <i class="fas fa-crown mr-1"></i> Pro
            </span>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-search-dollar text-violet-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Análise Financeira</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">IA analisa suas finanças e identifica padrões de gastos, oportunidades de economia e riscos.</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-bullseye text-rose-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Análise de Metas</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Avaliação inteligente do progresso das suas metas com sugestões para alcançá-las mais rápido.</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-route text-blue-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Plano por Objetivo</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">IA cria um plano financeiro personalizado com etapas claras para atingir seus objetivos.</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-file-contract text-green-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Parecer Técnico</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Receba um parecer técnico detalhado sobre sua situação financeira atual com recomendações.</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-map text-primary-600 text-lg"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Planejamento Financeiro</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">IA elabora um planejamento financeiro completo considerando renda, despesas, metas e perfil.</p>
            </div>
        </div>
    </div>
</section>

<!-- Urgency CTA -->
<section class="py-12 bg-yellow-50 dark:bg-yellow-900/10">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <div class="inline-flex items-center bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full px-4 py-1 text-sm font-semibold mb-4">
            <i class="fas fa-fire mr-2"></i> Últimas vagas com 40% de desconto
        </div>
        <h3 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-3">Não perca esta oportunidade</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-4">Plano Profissional de <span class="line-through">R$ 41,58</span> por apenas <strong class="text-primary-600">R$ 24,90/mês</strong>. Preço promocional por tempo limitado.</p>
        <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl transition shadow-lg text-lg">
            <i class="fas fa-rocket mr-2"></i> Começar Agora
        </a>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-3"><i class="fas fa-shield-alt mr-1"></i> Comece grátis. Upgrade quando quiser. Cancele a qualquer momento.</p>
    </div>
</section>

<!-- CTA -->
@include('components.cta.main')
@endsection
