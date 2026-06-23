@extends('layouts.landing')

@section('title', 'Serviços')
@section('meta_description', 'Conheça todos os serviços do Finestro: gestão de carteiras, controle de transações, metas financeiras, calculadoras, simuladores, IA e muito mais.')

@section('content')
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8 text-sm" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
                <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
                <li class="text-gray-700 dark:text-gray-300 font-medium">Serviços</li>
            </ol>
        </nav>

        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">Nossos Serviços</h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Soluções completas para gestão financeira pessoal e empresarial. Ferramentas gratuitas e recursos profissionais para quem quer ir além.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Gestão de Carteiras -->
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                        <i class="fas fa-check-circle mr-1"></i> Grátis
                    </span>
                </div>
                <div class="w-14 h-14 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-wallet text-2xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Gestão de Carteiras</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Crie e gerencie carteiras pessoais e empresariais. Organize suas contas, cartões e investimentos em um só lugar.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Carteiras pessoais e empresariais</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Saldos em tempo real</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Compartilhamento empresarial (Pro)</li>
                </ul>
            </div>

            <!-- Controle de Transações -->
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                        <i class="fas fa-check-circle mr-1"></i> Grátis
                    </span>
                </div>
                <div class="w-14 h-14 bg-secondary-100 dark:bg-secondary-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-exchange-alt text-2xl text-secondary-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Controle de Transações</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Registre todas as suas receitas e despesas com categorização inteligente. 100 transações/mês no plano gratuito, ilimitado no Pro.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Receitas e despesas</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Transações recorrentes</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Categorias personalizáveis</li>
                </ul>
            </div>

            <!-- Metas Financeiras -->
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                        <i class="fas fa-check-circle mr-1"></i> Grátis
                    </span>
                </div>
                <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-bullseye text-2xl text-purple-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Metas Financeiras</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Defina objetivos financeiros com prazos e acompanhe o progresso visual. 2 metas no gratuito, ilimitado no Pro.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Barra de progresso visual</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Acompanhamento de progresso</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Prazos e lembretes</li>
                </ul>
            </div>

            <!-- Calculadoras Financeiras -->
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                        <i class="fas fa-check-circle mr-1"></i> Grátis
                    </span>
                </div>
                <div class="w-14 h-14 bg-teal-100 dark:bg-teal-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-calculator text-2xl text-teal-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Calculadoras Financeiras</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">7 calculadoras gratuitas para ajudar nas suas decisões financeiras do dia a dia.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Investimento e juros compostos</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Aposentadoria e financiamento</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>13º, férias e cotação de moedas</li>
                </ul>
            </div>

            <!-- Simuladores Financeiros -->
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400">
                        <i class="fas fa-crown mr-1"></i> Pro
                    </span>
                </div>
                <div class="w-14 h-14 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-chart-line text-2xl text-orange-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Simuladores Financeiros</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">3 simuladores avançados para projetar seu futuro financeiro com cenários personalizados.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Simulador de investimentos</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Simulador de aposentadoria</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Simulador de orçamento</li>
                </ul>
            </div>

            <!-- Testes de Perfil -->
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                        <i class="fas fa-check-circle mr-1"></i> Grátis
                    </span>
                </div>
                <div class="w-14 h-14 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-user-check text-2xl text-indigo-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Testes de Perfil</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Descubra seu perfil financeiro e seu perfil de investidor com testes interativos e resultados personalizados.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Teste de perfil financeiro</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Teste de perfil investidor</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Resultados com orientações</li>
                </ul>
            </div>

            <!-- Relatórios e Gráficos -->
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400">
                        <i class="fas fa-crown mr-1"></i> Pro
                    </span>
                </div>
                <div class="w-14 h-14 bg-rose-100 dark:bg-rose-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-chart-pie text-2xl text-rose-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Relatórios e Gráficos</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Relatórios detalhados com gráficos interativos e exportação de dados em CSV para análise externa.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Gráficos interativos</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Exportação CSV</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Filtros por período e categoria</li>
                </ul>
            </div>

            <!-- Inteligência Artificial -->
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400">
                        <i class="fas fa-crown mr-1"></i> Pro
                    </span>
                </div>
                <div class="w-14 h-14 bg-violet-100 dark:bg-violet-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-robot text-2xl text-violet-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Inteligência Artificial</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">5 funcionalidades de IA para análises avançadas e planejamento financeiro personalizado.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Análise financeira e de metas</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Plano por objetivo</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Parecer técnico e planejamento</li>
                </ul>
            </div>

            <!-- Cotação de Moedas -->
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                        <i class="fas fa-check-circle mr-1"></i> Grátis
                    </span>
                </div>
                <div class="w-14 h-14 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-coins text-2xl text-amber-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Cotação de Moedas</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Acompanhe cotações de moedas em tempo real. Dólar, euro, libra e mais 6 moedas atualizadas automaticamente.</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Atualização em tempo real</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>9 moedas internacionais</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2"></i>Conversão instantânea</li>
                </ul>
            </div>
        </div>

        <!-- Plan comparison note -->
        <div class="mt-12 bg-white dark:bg-gray-900 rounded-2xl p-8 border border-gray-200 dark:border-gray-700 text-center">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Comece grátis, evolua quando quiser</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4 max-w-2xl mx-auto">
                O plano gratuito inclui carteiras, 100 transações/mês, 2 metas, 5 categorias, dashboard, 7 calculadoras, testes de perfil e cotação de moedas. Faça upgrade para o <strong>Plano Profissional</strong> e desbloqueie tudo ilimitado + IA + simuladores + relatórios.
            </p>
            <div class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <i class="fas fa-info-circle text-primary-500"></i>
                Sem compromisso. Cancele a qualquer momento.
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
