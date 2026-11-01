@extends('layouts.landing')

@section('title', 'Gestão Financeira Pessoal Inteligente')
@section('meta_description', 'Finestro - Controle suas finanças pessoais de forma simples, inteligente e segura. Carteiras, transações, metas, calculadoras e simuladores financeiros em um só lugar.')
@section('meta_keywords', 'finanças pessoais, controle financeiro, gestão de dinheiro, carteiras digitais, metas financeiras, simulador financeiro, calculadora financeira, orçamento pessoal, educação financeira')

@push('styles')
<style>
    /* Offset anchor targets so section titles are not hidden under the sticky header */
    [id] { scroll-margin-top: 5rem; }
    html { scroll-behavior: smooth; }
</style>
@endpush

@section('agency_cta_variant', 'landing')

@section('content')
@php
    // Sign-up links keep whitelisted UTM attribution from the landing URL.
    $signupUrl = route('register', \App\Support\Utm::fromRequest(request()));
    // Free-plan limits pulled from the same source enforcement uses, so the
    // pricing table never diverges from what the backend actually allows.
    $free = \App\Models\User::freePlanLimits();
@endphp

{{-- ============================================================= --}}
{{-- HERO                                                          --}}
{{-- ============================================================= --}}
<section id="inicio" class="relative overflow-hidden bg-gradient-to-br from-surface-950 via-primary-950 to-surface-900 text-white min-h-[90vh] flex items-center">
    <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute top-1/4 -left-20 w-96 h-96 bg-primary-500/15 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-1/4 -right-20 w-[500px] h-[500px] bg-accent-500/10 rounded-full blur-3xl animate-float" style="animation-delay: -3s"></div>
        <div class="absolute inset-0 bg-grid opacity-60"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-32 relative z-10 w-full">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full border border-white/10 mb-8">
                    <span class="w-2 h-2 bg-accent-400 rounded-full animate-pulse"></span>
                    <span class="text-sm text-primary-100/90">Gestão financeira pessoal, sem planilha</span>
                </div>
                <h1 class="font-display text-5xl sm:text-6xl lg:text-7xl font-semibold leading-[1.02] mb-6 tracking-tight text-white">
                    O seu dinheiro,<br>enfim sob controle
                </h1>
                <p class="text-lg sm:text-xl text-primary-100/80 mb-10 max-w-xl leading-relaxed">
                    Reúna carteiras, contas a pagar e metas em um só painel. Você vê para onde vai cada real e decide com clareza, não no escuro.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 mb-8">
                    <a href="{{ $signupUrl }}" data-loading="Carregando..." class="group inline-flex items-center justify-center px-8 py-4 bg-accent-400 hover:bg-accent-300 text-surface-950 font-bold rounded-2xl transition-all duration-300 shadow-lg shadow-accent-500/25 hover:shadow-xl hover:-translate-y-0.5">
                        <i class="fas fa-rocket mr-2" aria-hidden="true"></i> Criar conta grátis
                    </a>
                    <a href="#como-usar" class="inline-flex items-center justify-center px-8 py-4 bg-white/5 backdrop-blur-sm border border-white/15 text-white font-medium rounded-2xl hover:bg-white/10 transition-all duration-300">
                        <i class="fas fa-play-circle mr-2" aria-hidden="true"></i> Ver como funciona
                    </a>
                </div>
                <div class="flex items-center gap-6 text-sm text-primary-100/70">
                    <span class="flex items-center gap-1.5"><i class="fas fa-check-circle text-accent-400" aria-hidden="true"></i> Sem cartão de crédito</span>
                    <span class="flex items-center gap-1.5"><i class="fas fa-check-circle text-accent-400" aria-hidden="true"></i> Pronto em 2 minutos</span>
                </div>
            </div>
            <div class="hidden lg:block">
                <div class="relative">
                    <div class="absolute -inset-4 bg-gradient-to-r from-primary-500/20 to-secondary-500/20 rounded-3xl blur-2xl"></div>
                    <div class="relative bg-white/5 backdrop-blur-xl rounded-3xl p-8 border border-white/10">
                        <div class="space-y-5">
                            <div class="flex items-center justify-between bg-white/5 rounded-2xl p-5 border border-white/5">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-secondary-400 to-secondary-600 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-arrow-up text-white" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-primary-100/60">Receitas do mês</p>
                                        <p class="num text-2xl font-semibold text-white">R$ 8.450</p>
                                    </div>
                                </div>
                                <span class="num px-3 py-1 bg-secondary-500/20 text-secondary-300 text-sm font-semibold rounded-lg">+12%</span>
                            </div>
                            <div class="flex items-center justify-between bg-white/5 rounded-2xl p-5 border border-white/5">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-red-600 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-arrow-down text-white" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-primary-100/60">Despesas do mês</p>
                                        <p class="num text-2xl font-semibold text-white">R$ 3.280</p>
                                    </div>
                                </div>
                                <span class="num px-3 py-1 bg-red-500/20 text-red-300 text-sm font-semibold rounded-lg">-5%</span>
                            </div>
                            <div class="flex items-center justify-between bg-white/5 rounded-2xl p-5 border border-white/5">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-accent-400 to-accent-600 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-piggy-bank text-white" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-primary-100/60">Economia</p>
                                        <p class="num text-2xl font-semibold text-white">R$ 5.170</p>
                                    </div>
                                </div>
                                <span class="num px-3 py-1 bg-accent-500/20 text-accent-300 text-sm font-semibold rounded-lg">61%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================= --}}
{{-- FEATURES                                                      --}}
{{-- ============================================================= --}}
<section id="features" class="py-24 bg-white dark:bg-surface-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="inline-flex items-center px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-full mb-4">
                <i class="fas fa-star mr-1.5" aria-hidden="true"></i> Funcionalidades
            </span>
            <h2 class="font-display text-3xl md:text-4xl lg:text-5xl font-semibold text-surface-900 dark:text-white mb-4">
                Tudo que você precisa para suas finanças
            </h2>
            <p class="text-lg text-surface-600 dark:text-surface-400 max-w-2xl mx-auto">
                Funcionalidades pensadas para simplificar o controle do seu dinheiro e ajudar você a alcançar seus objetivos.
            </p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            <div class="group bg-surface-50 dark:bg-surface-800 rounded-2xl p-7 border border-surface-100 dark:border-surface-700 hover:border-primary-200 dark:hover:border-primary-800 hover:shadow-soft transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-primary-500/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-wallet text-white text-xl" aria-hidden="true"></i>
                </div>
                <h3 class="text-lg font-bold text-surface-900 dark:text-white mb-2">Múltiplas Carteiras</h3>
                <p class="text-surface-600 dark:text-surface-400 leading-relaxed">Organize suas contas bancárias, cartões e investimentos em carteiras separadas com saldos em tempo real.</p>
            </div>
            <div class="group bg-surface-50 dark:bg-surface-800 rounded-2xl p-7 border border-surface-100 dark:border-surface-700 hover:border-secondary-200 dark:hover:border-secondary-800 hover:shadow-soft transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-secondary-500 to-secondary-600 rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-secondary-500/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-exchange-alt text-white text-xl" aria-hidden="true"></i>
                </div>
                <h3 class="text-lg font-bold text-surface-900 dark:text-white mb-2">Controle de Transações</h3>
                <p class="text-surface-600 dark:text-surface-400 leading-relaxed">Registre receitas e despesas, categorize e acompanhe para onde vai cada centavo do seu dinheiro.</p>
            </div>
            <div class="group bg-surface-50 dark:bg-surface-800 rounded-2xl p-7 border border-surface-100 dark:border-surface-700 hover:border-accent-200 dark:hover:border-accent-800 hover:shadow-soft transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-accent-500 to-accent-600 rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-accent-500/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-bullseye text-white text-xl" aria-hidden="true"></i>
                </div>
                <h3 class="text-lg font-bold text-surface-900 dark:text-white mb-2">Metas Financeiras</h3>
                <p class="text-surface-600 dark:text-surface-400 leading-relaxed">Defina objetivos, acompanhe o progresso e celebre cada conquista no caminho da liberdade financeira.</p>
            </div>
            <div class="group bg-surface-50 dark:bg-surface-800 rounded-2xl p-7 border border-surface-100 dark:border-surface-700 hover:border-orange-200 dark:hover:border-orange-800 hover:shadow-soft transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-orange-500/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-chart-pie text-white text-xl" aria-hidden="true"></i>
                </div>
                <h3 class="text-lg font-bold text-surface-900 dark:text-white mb-2">Relatórios Visuais</h3>
                <p class="text-surface-600 dark:text-surface-400 leading-relaxed">Gráficos e dashboards intuitivos para visualizar sua saúde financeira de forma clara e objetiva.</p>
            </div>
            <div class="group bg-surface-50 dark:bg-surface-800 rounded-2xl p-7 border border-surface-100 dark:border-surface-700 hover:border-red-200 dark:hover:border-red-800 hover:shadow-soft transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-600 rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-red-500/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-shield-alt text-white text-xl" aria-hidden="true"></i>
                </div>
                <h3 class="text-lg font-bold text-surface-900 dark:text-white mb-2">Segurança Total</h3>
                <p class="text-surface-600 dark:text-surface-400 leading-relaxed">Seus dados são protegidos com criptografia e conformidade LGPD. Apenas você acessa suas informações.</p>
            </div>
            <div class="group bg-surface-50 dark:bg-surface-800 rounded-2xl p-7 border border-surface-100 dark:border-surface-700 hover:border-teal-200 dark:hover:border-teal-800 hover:shadow-soft transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-teal-500 to-teal-600 rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-teal-500/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-mobile-alt text-white text-xl" aria-hidden="true"></i>
                </div>
                <h3 class="text-lg font-bold text-surface-900 dark:text-white mb-2">Acesso em Qualquer Lugar</h3>
                <p class="text-surface-600 dark:text-surface-400 leading-relaxed">Interface responsiva e otimizada para celular, tablet e desktop. Suas finanças sempre ao alcance.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================= --}}
{{-- SOBRE (from pages/about)                                      --}}
{{-- ============================================================= --}}
<section id="sobre" class="py-24 bg-surface-50 dark:bg-surface-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <span class="inline-flex items-center px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-full mb-4">
                <i class="fas fa-circle-info mr-1.5" aria-hidden="true"></i> Sobre o Finestro
            </span>
            <h2 class="font-display text-3xl md:text-4xl font-semibold text-surface-900 dark:text-white mb-4">Quem somos</h2>
        </div>

        <div class="grid md:grid-cols-2 gap-12 items-center mb-16">
            <div>
                <p class="text-lg text-surface-600 dark:text-surface-400 mb-4">
                    O <strong>Finestro</strong> é uma plataforma de gestão financeira pessoal e empresarial desenvolvida para transformar a forma como você controla seu dinheiro.
                </p>
                <p class="text-surface-600 dark:text-surface-400 mb-4">
                    Combinamos tecnologia de ponta com inteligência artificial para oferecer uma experiência completa: desde o controle básico de receitas e despesas até análises financeiras personalizadas e simulações de investimentos.
                </p>
                <p class="text-surface-600 dark:text-surface-400 mb-6">
                    Nossa missão é democratizar o acesso à educação financeira e dar ferramentas práticas para que qualquer pessoa possa prosperar financeiramente.
                </p>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center gap-2 text-sm text-surface-700 dark:text-surface-300">
                        <i class="fas fa-check-circle text-secondary-500" aria-hidden="true"></i> Conformidade LGPD
                    </div>
                    <div class="flex items-center gap-2 text-sm text-surface-700 dark:text-surface-300">
                        <i class="fas fa-check-circle text-secondary-500" aria-hidden="true"></i> Dados criptografados
                    </div>
                    <div class="flex items-center gap-2 text-sm text-surface-700 dark:text-surface-300">
                        <i class="fas fa-check-circle text-secondary-500" aria-hidden="true"></i> IA integrada
                    </div>
                    <div class="flex items-center gap-2 text-sm text-surface-700 dark:text-surface-300">
                        <i class="fas fa-check-circle text-secondary-500" aria-hidden="true"></i> Suporte dedicado
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-surface-900 rounded-2xl p-8 shadow-lg">
                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-eye text-primary-600" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-surface-900 dark:text-white">Nossa Visão</h3>
                            <p class="text-sm text-surface-600 dark:text-surface-400">Ser a principal plataforma de gestão financeira pessoal do Brasil, acessível a todos que desejam prosperar.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-secondary-100 dark:bg-secondary-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-heart text-secondary-600" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-surface-900 dark:text-white">Nossa Missão</h3>
                            <p class="text-sm text-surface-600 dark:text-surface-400">Oferecer ferramentas inteligentes e acessíveis que transformem a relação das pessoas com o dinheiro, promovendo educação e controle financeiro.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-star text-purple-600" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-surface-900 dark:text-white">Nossos Valores</h3>
                            <p class="text-sm text-surface-600 dark:text-surface-400">Transparência, segurança, inovação, simplicidade e compromisso com o sucesso financeiro do usuário.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tecnologia --}}
        <div class="text-center mb-12">
            <h3 class="text-2xl md:text-3xl font-bold text-surface-900 dark:text-white mb-4">Tecnologia de Ponta</h3>
            <p class="text-lg text-surface-600 dark:text-surface-400 max-w-2xl mx-auto">
                Desenvolvido com as melhores tecnologias do mercado para garantir performance, segurança e escalabilidade.
            </p>
        </div>
        <div class="grid md:grid-cols-3 gap-8 mb-16">
            <div class="text-center p-6 bg-white dark:bg-surface-900 rounded-2xl border border-surface-100 dark:border-surface-700">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fab fa-laravel text-3xl text-red-600" aria-hidden="true"></i>
                </div>
                <h4 class="font-bold text-surface-900 dark:text-white mb-2">Laravel Framework</h4>
                <p class="text-sm text-surface-600 dark:text-surface-400">Framework PHP robusto e moderno, utilizado por milhões de desenvolvedores ao redor do mundo.</p>
            </div>
            <div class="text-center p-6 bg-white dark:bg-surface-900 rounded-2xl border border-surface-100 dark:border-surface-700">
                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-robot text-3xl text-purple-600" aria-hidden="true"></i>
                </div>
                <h4 class="font-bold text-surface-900 dark:text-white mb-2">Inteligência Artificial</h4>
                <p class="text-sm text-surface-600 dark:text-surface-400">IA avançada para análises financeiras, planos personalizados e insights inteligentes sobre suas finanças.</p>
            </div>
            <div class="text-center p-6 bg-white dark:bg-surface-900 rounded-2xl border border-surface-100 dark:border-surface-700">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-lock text-3xl text-green-600" aria-hidden="true"></i>
                </div>
                <h4 class="font-bold text-surface-900 dark:text-white mb-2">Criptografia Avançada</h4>
                <p class="text-sm text-surface-600 dark:text-surface-400">Dados protegidos com criptografia de ponta a ponta. Apenas você tem acesso às suas informações financeiras.</p>
            </div>
        </div>

        {{-- "Como Funciona" moved to the #como-usar section (LandingContent::steps()) --}}

        {{-- Compromissos --}}
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <h3 class="text-2xl font-bold text-surface-900 dark:text-white mb-4">Desenvolvido pelo Grupo 3W Online</h3>
                <p class="text-surface-600 dark:text-surface-400 mb-4">
                    O Finestro é um produto do <strong>Grupo 3W Online</strong>, empresa especializada em desenvolvimento de sites e sistemas sob demanda com tecnologia de ponta.
                </p>
                <p class="text-surface-600 dark:text-surface-400 mb-6">
                    Com expertise em projetos digitais e foco em resultados, nossa equipe combina design moderno, performance e segurança para entregar soluções que realmente fazem a diferença.
                </p>
                <a href="https://www.3wonline.com.br" target="_blank" rel="noopener" aria-label="Conheça o Grupo 3W Online (abre em nova aba)" class="inline-flex items-center text-primary-600 hover:text-primary-700 font-semibold">
                    <i class="fas fa-external-link-alt mr-2" aria-hidden="true"></i> Conheça o Grupo 3W Online
                </a>
            </div>
            <div class="bg-white dark:bg-surface-900 rounded-2xl p-8 border border-surface-100 dark:border-surface-700">
                <h4 class="font-bold text-surface-900 dark:text-white mb-6 text-lg">Nossos Compromissos</h4>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-shield-alt text-green-500 mt-1" aria-hidden="true"></i>
                        <div>
                            <p class="font-semibold text-surface-900 dark:text-white text-sm">Segurança e Privacidade Global</p>
                            <p class="text-xs text-surface-600 dark:text-surface-400">Em conformidade com LGPD, GDPR, CCPA e POPIA.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-user-lock text-blue-500 mt-1" aria-hidden="true"></i>
                        <div>
                            <p class="font-semibold text-surface-900 dark:text-white text-sm">Privacidade Total</p>
                            <p class="text-xs text-surface-600 dark:text-surface-400">Cada usuário acessa exclusivamente seus próprios dados.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-database text-purple-500 mt-1" aria-hidden="true"></i>
                        <div>
                            <p class="font-semibold text-surface-900 dark:text-white text-sm">Dados Criptografados</p>
                            <p class="text-xs text-surface-600 dark:text-surface-400">Informações protegidas com criptografia de ponta a ponta.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-trash-alt text-red-500 mt-1" aria-hidden="true"></i>
                        <div>
                            <p class="font-semibold text-surface-900 dark:text-white text-sm">Direito ao Esquecimento</p>
                            <p class="text-xs text-surface-600 dark:text-surface-400">Você pode solicitar a exclusão total dos seus dados a qualquer momento.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- ============================================================= --}}
{{-- SERVIÇOS (from pages/services)                                --}}
{{-- ============================================================= --}}
<section id="servicos" class="py-24 bg-white dark:bg-surface-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <span class="inline-flex items-center px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-full mb-4">
                <i class="fas fa-layer-group mr-1.5" aria-hidden="true"></i> Serviços
            </span>
            <h2 class="font-display text-3xl md:text-4xl font-semibold text-surface-900 dark:text-white mb-4">Nossos Serviços</h2>
            <p class="text-lg text-surface-600 dark:text-surface-400 max-w-2xl mx-auto">
                Soluções completas para gestão financeira pessoal e empresarial. Ferramentas gratuitas e recursos profissionais para quem quer ir além.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="bg-surface-50 dark:bg-surface-800 rounded-xl p-6 shadow-sm border border-surface-200 dark:border-surface-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"><i class="fas fa-check-circle mr-1" aria-hidden="true"></i> Grátis</span></div>
                <div class="w-14 h-14 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-wallet text-2xl text-primary-600" aria-hidden="true"></i></div>
                <h3 class="text-xl font-semibold text-surface-900 dark:text-white mb-3">Gestão de Carteiras</h3>
                <p class="text-surface-600 dark:text-surface-400 mb-4">Crie e gerencie carteiras pessoais e empresariais. Organize suas contas, cartões e investimentos em um só lugar.</p>
                <ul class="space-y-2 text-sm text-surface-600 dark:text-surface-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Carteiras pessoais e empresariais</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Saldos em tempo real</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Compartilhamento empresarial (Profissional)</li>
                </ul>
            </div>
            <div class="bg-surface-50 dark:bg-surface-800 rounded-xl p-6 shadow-sm border border-surface-200 dark:border-surface-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"><i class="fas fa-check-circle mr-1" aria-hidden="true"></i> Grátis</span></div>
                <div class="w-14 h-14 bg-secondary-100 dark:bg-secondary-900/30 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-exchange-alt text-2xl text-secondary-600" aria-hidden="true"></i></div>
                <h3 class="text-xl font-semibold text-surface-900 dark:text-white mb-3">Controle de Transações</h3>
                <p class="text-surface-600 dark:text-surface-400 mb-4">Registre todas as suas receitas e despesas com categorização inteligente. {{ $free['transactions_per_month'] }} transações/mês no plano gratuito, ilimitado no Profissional.</p>
                <ul class="space-y-2 text-sm text-surface-600 dark:text-surface-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Receitas e despesas</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Transações recorrentes</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Categorias personalizáveis</li>
                </ul>
            </div>
            <div class="bg-surface-50 dark:bg-surface-800 rounded-xl p-6 shadow-sm border border-surface-200 dark:border-surface-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"><i class="fas fa-check-circle mr-1" aria-hidden="true"></i> Grátis</span></div>
                <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-bullseye text-2xl text-purple-600" aria-hidden="true"></i></div>
                <h3 class="text-xl font-semibold text-surface-900 dark:text-white mb-3">Metas Financeiras</h3>
                <p class="text-surface-600 dark:text-surface-400 mb-4">Defina objetivos financeiros com prazos e acompanhe o progresso visual. {{ $free['goals'] }} metas no gratuito, ilimitado no Profissional.</p>
                <ul class="space-y-2 text-sm text-surface-600 dark:text-surface-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Barra de progresso visual</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Acompanhamento de progresso</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Prazos e lembretes</li>
                </ul>
            </div>
            <div class="bg-surface-50 dark:bg-surface-800 rounded-xl p-6 shadow-sm border border-surface-200 dark:border-surface-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"><i class="fas fa-check-circle mr-1" aria-hidden="true"></i> Grátis</span></div>
                <div class="w-14 h-14 bg-teal-100 dark:bg-teal-900/30 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-calculator text-2xl text-teal-600" aria-hidden="true"></i></div>
                <h3 class="text-xl font-semibold text-surface-900 dark:text-white mb-3">Calculadoras Financeiras</h3>
                <p class="text-surface-600 dark:text-surface-400 mb-4">7 calculadoras gratuitas para ajudar nas suas decisões financeiras do dia a dia.</p>
                <ul class="space-y-2 text-sm text-surface-600 dark:text-surface-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Investimento e juros compostos</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Aposentadoria e financiamento</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>13º, férias e cotação de moedas</li>
                </ul>
            </div>
            <div class="bg-surface-50 dark:bg-surface-800 rounded-xl p-6 shadow-sm border border-surface-200 dark:border-surface-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400"><i class="fas fa-crown mr-1" aria-hidden="true"></i> Profissional</span></div>
                <div class="w-14 h-14 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-chart-line text-2xl text-orange-600" aria-hidden="true"></i></div>
                <h3 class="text-xl font-semibold text-surface-900 dark:text-white mb-3">Simuladores Financeiros</h3>
                <p class="text-surface-600 dark:text-surface-400 mb-4">3 simuladores avançados para projetar seu futuro financeiro com cenários personalizados.</p>
                <ul class="space-y-2 text-sm text-surface-600 dark:text-surface-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Simulador de investimentos</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Simulador de aposentadoria</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Simulador de orçamento</li>
                </ul>
            </div>
            <div class="bg-surface-50 dark:bg-surface-800 rounded-xl p-6 shadow-sm border border-surface-200 dark:border-surface-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"><i class="fas fa-check-circle mr-1" aria-hidden="true"></i> Grátis</span></div>
                <div class="w-14 h-14 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-user-check text-2xl text-indigo-600" aria-hidden="true"></i></div>
                <h3 class="text-xl font-semibold text-surface-900 dark:text-white mb-3">Testes de Perfil</h3>
                <p class="text-surface-600 dark:text-surface-400 mb-4">Descubra seu perfil financeiro e seu perfil de investidor com testes interativos e resultados personalizados.</p>
                <ul class="space-y-2 text-sm text-surface-600 dark:text-surface-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Teste de perfil financeiro</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Teste de perfil investidor</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Resultados com orientações</li>
                </ul>
            </div>
            <div class="bg-surface-50 dark:bg-surface-800 rounded-xl p-6 shadow-sm border border-surface-200 dark:border-surface-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400"><i class="fas fa-crown mr-1" aria-hidden="true"></i> Profissional</span></div>
                <div class="w-14 h-14 bg-rose-100 dark:bg-rose-900/30 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-chart-pie text-2xl text-rose-600" aria-hidden="true"></i></div>
                <h3 class="text-xl font-semibold text-surface-900 dark:text-white mb-3">Relatórios e Gráficos</h3>
                <p class="text-surface-600 dark:text-surface-400 mb-4">Relatórios detalhados com gráficos interativos e exportação de dados em CSV para análise externa.</p>
                <ul class="space-y-2 text-sm text-surface-600 dark:text-surface-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Gráficos interativos</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Exportação CSV</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Filtros por período e categoria</li>
                </ul>
            </div>
            <div class="bg-surface-50 dark:bg-surface-800 rounded-xl p-6 shadow-sm border border-surface-200 dark:border-surface-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400"><i class="fas fa-crown mr-1" aria-hidden="true"></i> Profissional</span></div>
                <div class="w-14 h-14 bg-violet-100 dark:bg-violet-900/30 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-robot text-2xl text-violet-600" aria-hidden="true"></i></div>
                <h3 class="text-xl font-semibold text-surface-900 dark:text-white mb-3">Inteligência Artificial</h3>
                <p class="text-surface-600 dark:text-surface-400 mb-4">5 funcionalidades de IA para análises avançadas e planejamento financeiro personalizado.</p>
                <ul class="space-y-2 text-sm text-surface-600 dark:text-surface-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Análise financeira e de metas</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Plano por objetivo</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Parecer técnico e planejamento</li>
                </ul>
            </div>
            <div class="bg-surface-50 dark:bg-surface-800 rounded-xl p-6 shadow-sm border border-surface-200 dark:border-surface-700 hover:shadow-lg transition relative">
                <div class="absolute top-4 right-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"><i class="fas fa-check-circle mr-1" aria-hidden="true"></i> Grátis</span></div>
                <div class="w-14 h-14 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-coins text-2xl text-amber-600" aria-hidden="true"></i></div>
                <h3 class="text-xl font-semibold text-surface-900 dark:text-white mb-3">Cotação de Moedas</h3>
                <p class="text-surface-600 dark:text-surface-400 mb-4">Acompanhe cotações de moedas em tempo real. Dólar, euro, libra e mais 6 moedas atualizadas automaticamente.</p>
                <ul class="space-y-2 text-sm text-surface-600 dark:text-surface-400">
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Atualização em tempo real</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>9 moedas internacionais</li>
                    <li><i class="fas fa-check text-secondary-500 mr-2" aria-hidden="true"></i>Conversão instantânea</li>
                </ul>
            </div>
        </div>

        <div class="mt-12 bg-surface-50 dark:bg-surface-800 rounded-2xl p-8 border border-surface-200 dark:border-surface-700 text-center">
            <h3 class="text-xl font-bold text-surface-900 dark:text-white mb-3">Comece grátis, evolua quando quiser</h3>
            <p class="text-surface-600 dark:text-surface-400 mb-4 max-w-2xl mx-auto">
                O plano gratuito inclui carteiras, {{ $free['transactions_per_month'] }} transações/mês, {{ $free['goals'] }} metas, {{ $free['categories'] }} categorias, dashboard, 7 calculadoras, testes de perfil e cotação de moedas. Faça upgrade para o <strong>Plano Profissional</strong> e desbloqueie tudo ilimitado + IA + simuladores + relatórios.
            </p>
            <div class="inline-flex items-center gap-2 text-sm text-surface-500 dark:text-surface-400">
                <i class="fas fa-info-circle text-primary-500" aria-hidden="true"></i> Sem compromisso. Cancele a qualquer momento.
            </div>
        </div>
    </div>
</section>

{{-- ============================================================= --}}
{{-- FERRAMENTAS (from pages/tools)                                --}}
{{-- ============================================================= --}}
<section id="ferramentas" class="py-24 bg-surface-50 dark:bg-surface-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <span class="inline-flex items-center px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-full mb-4">
                <i class="fas fa-toolbox mr-1.5" aria-hidden="true"></i> Ferramentas
            </span>
            <h2 class="font-display text-3xl md:text-4xl font-semibold text-surface-900 dark:text-white mb-4">Ferramentas Financeiras</h2>
            <p class="text-lg text-surface-600 dark:text-surface-400 max-w-2xl mx-auto">
                Calculadoras, simuladores, testes e inteligência artificial para decisões financeiras mais inteligentes.
            </p>
        </div>

        {{-- Calculadoras --}}
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900/30 rounded-lg flex items-center justify-center"><i class="fas fa-calculator text-teal-600" aria-hidden="true"></i></div>
            <div>
                <h3 class="text-2xl font-bold text-surface-900 dark:text-white">Calculadoras Financeiras</h3>
                <p class="text-sm text-surface-500 dark:text-surface-400">7 calculadoras gratuitas para todos os usuários</p>
            </div>
            <span class="ml-auto inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"><i class="fas fa-check-circle mr-1" aria-hidden="true"></i> Grátis</span>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-16">
            <div class="bg-white dark:bg-surface-900 rounded-xl p-5 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-chart-line text-primary-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Investimento</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Calcule rendimentos com juros compostos, aportes mensais e diferentes prazos.</p></div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-5 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-percentage text-orange-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Juros</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Compare juros simples e compostos. Calcule o custo real de empréstimos.</p></div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-5 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-umbrella-beach text-secondary-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Aposentadoria</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Descubra quanto poupar por mês para se aposentar com tranquilidade.</p></div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-5 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-gift text-red-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">13º Salário</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Calcule o valor líquido do seu 13º considerando descontos e parcelas.</p></div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-5 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-plane text-blue-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Férias</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Calcule o valor das suas férias com abono, descontos e adiantamento do 13º.</p></div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-5 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-home text-purple-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Financiamento</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Simule parcelas de financiamento imobiliário ou veicular com diferentes taxas.</p></div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-5 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-coins text-amber-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Cotação de Moedas</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Converta valores entre moedas com cotações atualizadas em tempo real.</p></div>
        </div>

        {{-- Simuladores --}}
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center"><i class="fas fa-flask text-orange-600" aria-hidden="true"></i></div>
            <div>
                <h3 class="text-2xl font-bold text-surface-900 dark:text-white">Simuladores Financeiros</h3>
                <p class="text-sm text-surface-500 dark:text-surface-400">3 simuladores avançados com cenários personalizados</p>
            </div>
            <span class="ml-auto inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400"><i class="fas fa-crown mr-1" aria-hidden="true"></i> Profissional</span>
        </div>
        <div class="grid md:grid-cols-3 gap-6 mb-16">
            <div class="bg-white dark:bg-surface-900 rounded-xl p-6 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-chart-area text-primary-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Simulador de Investimentos</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Projete o crescimento do seu patrimônio com diferentes cenários de aportes, taxas e prazos.</p></div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-6 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-umbrella-beach text-secondary-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Simulador de Aposentadoria</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Planeje sua aposentadoria com projeções detalhadas de renda, despesas e patrimônio acumulado.</p></div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-6 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-balance-scale text-purple-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Simulador de Orçamento</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Monte orçamentos mensais com distribuição inteligente entre necessidades, desejos e investimentos.</p></div>
        </div>

        {{-- Testes de Perfil --}}
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center"><i class="fas fa-user-check text-indigo-600" aria-hidden="true"></i></div>
            <div>
                <h3 class="text-2xl font-bold text-surface-900 dark:text-white">Testes de Perfil</h3>
                <p class="text-sm text-surface-500 dark:text-surface-400">Descubra seu perfil financeiro e de investidor</p>
            </div>
            <span class="ml-auto inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"><i class="fas fa-check-circle mr-1" aria-hidden="true"></i> Grátis</span>
        </div>
        <div class="grid md:grid-cols-2 gap-6 mb-16">
            <div class="bg-white dark:bg-surface-900 rounded-xl p-6 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-brain text-indigo-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Teste de Perfil Financeiro</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Avalie seus hábitos financeiros e descubra se você é poupador, equilibrado ou gastador. Receba orientações personalizadas.</p></div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-6 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-chart-bar text-teal-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Teste de Perfil Investidor</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Identifique se você é conservador, moderado ou arrojado. Descubra investimentos adequados ao seu perfil de risco.</p></div>
        </div>

        {{-- Cotação de Moedas --}}
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center"><i class="fas fa-coins text-amber-600" aria-hidden="true"></i></div>
            <div>
                <h3 class="text-2xl font-bold text-surface-900 dark:text-white">Cotação de Moedas</h3>
                <p class="text-sm text-surface-500 dark:text-surface-400">Acompanhe 9 moedas em tempo real</p>
            </div>
            <span class="ml-auto inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"><i class="fas fa-check-circle mr-1" aria-hidden="true"></i> Grátis</span>
        </div>
        <div class="bg-white dark:bg-surface-900 rounded-xl p-6 border border-surface-200 dark:border-surface-700 mb-16">
            <div class="grid md:grid-cols-3 gap-6">
                <div>
                    <h4 class="font-semibold text-surface-900 dark:text-white mb-3">Moedas Disponíveis</h4>
                    <ul class="space-y-2 text-sm text-surface-600 dark:text-surface-400">
                        <li><i class="fas fa-dollar-sign text-green-500 mr-2" aria-hidden="true"></i>Dólar Americano (USD)</li>
                        <li><i class="fas fa-euro-sign text-blue-500 mr-2" aria-hidden="true"></i>Euro (EUR)</li>
                        <li><i class="fas fa-pound-sign text-purple-500 mr-2" aria-hidden="true"></i>Libra Esterlina (GBP)</li>
                    </ul>
                </div>
                <div>
                    <ul class="space-y-2 text-sm text-surface-600 dark:text-surface-400 md:mt-8">
                        <li><i class="fas fa-yen-sign text-red-500 mr-2" aria-hidden="true"></i>Iene Japonês (JPY)</li>
                        <li><i class="fas fa-dollar-sign text-teal-500 mr-2" aria-hidden="true"></i>Dólar Canadense (CAD)</li>
                        <li><i class="fas fa-dollar-sign text-orange-500 mr-2" aria-hidden="true"></i>Dólar Australiano (AUD)</li>
                    </ul>
                </div>
                <div>
                    <ul class="space-y-2 text-sm text-surface-600 dark:text-surface-400 md:mt-8">
                        <li><i class="fas fa-money-bill text-surface-500 mr-2" aria-hidden="true"></i>Franco Suíço (CHF)</li>
                        <li><i class="fas fa-yen-sign text-indigo-500 mr-2" aria-hidden="true"></i>Yuan Chinês (CNY)</li>
                        <li><i class="fas fa-money-bill text-amber-500 mr-2" aria-hidden="true"></i>Peso Argentino (ARS)</li>
                    </ul>
                </div>
            </div>
            <p class="mt-4 text-xs text-surface-500 dark:text-surface-400"><i class="fas fa-sync-alt mr-1" aria-hidden="true"></i> Cotações atualizadas automaticamente em tempo real.</p>
        </div>

        {{-- Inteligência Artificial --}}
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-violet-100 dark:bg-violet-900/30 rounded-lg flex items-center justify-center"><i class="fas fa-robot text-violet-600" aria-hidden="true"></i></div>
            <div>
                <h3 class="text-2xl font-bold text-surface-900 dark:text-white">Inteligência Artificial</h3>
                <p class="text-sm text-surface-500 dark:text-surface-400">5 funcionalidades de IA para análises avançadas</p>
            </div>
            <span class="ml-auto inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400"><i class="fas fa-crown mr-1" aria-hidden="true"></i> Profissional</span>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-surface-900 rounded-xl p-5 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-search-dollar text-violet-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Análise Financeira</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">IA analisa suas finanças e identifica padrões de gastos, oportunidades de economia e riscos.</p></div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-5 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-bullseye text-rose-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Análise de Metas</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Avaliação inteligente do progresso das suas metas com sugestões para alcançá-las mais rápido.</p></div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-5 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-route text-blue-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Plano por Objetivo</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">IA cria um plano financeiro personalizado com etapas claras para atingir seus objetivos.</p></div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-5 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-file-contract text-green-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Parecer Técnico</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">Receba um parecer técnico detalhado sobre sua situação financeira atual com recomendações.</p></div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-5 border border-surface-200 dark:border-surface-700"><div class="flex items-center gap-3 mb-3"><i class="fas fa-map text-primary-600 text-lg" aria-hidden="true"></i><h4 class="font-semibold text-surface-900 dark:text-white">Planejamento Financeiro</h4></div><p class="text-sm text-surface-600 dark:text-surface-400">IA elabora um planejamento financeiro completo considerando renda, despesas, metas e perfil.</p></div>
        </div>
    </div>
</section>

{{-- ============================================================= --}}
{{-- COMO USAR (steps)                                             --}}
{{-- ============================================================= --}}
<section id="como-usar" class="py-24 bg-white dark:bg-surface-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="inline-flex items-center px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-full mb-4">
                <i class="fas fa-list-ol mr-1.5" aria-hidden="true"></i> Como usar
            </span>
            <h2 class="font-display text-3xl md:text-4xl font-semibold text-surface-900 dark:text-white mb-4">Como Funciona</h2>
            <p class="text-lg text-surface-600 dark:text-surface-400">Em 3 passos simples você já está no controle das suas finanças.</p>
        </div>
        @php $stepColors = ['bg-primary-600', 'bg-secondary-600', 'bg-accent-600']; @endphp
        <ol class="grid md:grid-cols-3 gap-8 relative">
            <li class="hidden md:block absolute top-7 left-1/4 right-1/4 h-0.5 bg-gradient-to-r from-primary-300 via-secondary-300 to-accent-300 dark:from-primary-700 dark:via-secondary-700 dark:to-accent-700" aria-hidden="true"></li>
            @foreach(\App\Support\Landing\LandingContent::steps() as $i => $step)
            <li class="text-center relative">
                <div class="w-14 h-14 {{ $stepColors[$i % 3] }} rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg relative z-10">
                    <span class="text-xl font-bold text-white">{{ $i + 1 }}</span>
                </div>
                <h3 class="text-lg font-bold text-surface-900 dark:text-white mb-2"><i class="fas {{ $step['icon'] }} text-primary-500 mr-1.5" aria-hidden="true"></i>{{ $step['title'] }}</h3>
                <p class="text-surface-600 dark:text-surface-400">{{ $step['text'] }}</p>
            </li>
            @endforeach
        </ol>
        <div class="mt-12 max-w-3xl mx-auto">
            <x-ui.tip icon="fa-lightbulb">
                <strong>Exemplo de uso:</strong> crie uma carteira "Conta corrente", lance o salário como receita e as contas do mês como despesas. No dashboard você vê na hora quanto sobra e pode criar uma meta, como "Reserva de emergência", para guardar esse valor.
            </x-ui.tip>
        </div>
        <div class="mt-10 text-center">
            <a href="{{ $signupUrl }}" data-loading="Carregando..." class="inline-flex items-center justify-center px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-2xl transition shadow-lg">
                <i class="fas fa-user-plus mr-2" aria-hidden="true"></i> Começar o passo 1 agora
            </a>
        </div>
    </div>
</section>

{{-- ============================================================= --}}
{{-- DICAS                                                         --}}
{{-- ============================================================= --}}
<section id="dicas" class="py-24 bg-surface-50 dark:bg-surface-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-display text-3xl md:text-4xl font-semibold text-surface-900 dark:text-white mb-4">
                <i class="fas fa-lightbulb text-accent-500 mr-2" aria-hidden="true"></i> Dicas Financeiras
            </h2>
            <p class="text-lg text-surface-600 dark:text-surface-400">Orientações práticas para melhorar sua vida financeira.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach(\App\Support\Landing\LandingContent::tips() as $tip)
            <div class="bg-white dark:bg-surface-900 rounded-2xl p-6 border border-surface-200 dark:border-surface-700">
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center mb-4"><i class="fas {{ $tip['icon'] }} text-xl text-primary-600" aria-hidden="true"></i></div>
                <h3 class="font-bold text-surface-900 dark:text-white mb-2">{{ $tip['title'] }}</h3>
                <p class="text-sm text-surface-600 dark:text-surface-400">{{ $tip['text'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================= --}}
{{-- PLANOS (pricing)                                              --}}
{{-- ============================================================= --}}
<section id="planos" class="py-24 bg-white dark:bg-surface-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="inline-flex items-center px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-full mb-4">
                <i class="fas fa-tags mr-1.5" aria-hidden="true"></i> Planos
            </span>
            <h2 class="font-display text-3xl md:text-4xl lg:text-5xl font-semibold text-surface-900 dark:text-white mb-4">
                Escolha o plano ideal para você
            </h2>
            <p class="text-lg text-surface-600 dark:text-surface-400 max-w-2xl mx-auto">
                Comece grátis e evolua quando precisar. Sem compromisso, cancele a qualquer momento.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
            <div class="bg-white dark:bg-surface-900 rounded-2xl p-8 border border-surface-200 dark:border-surface-700">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-surface-900 dark:text-white mb-1">Básico</h3>
                    <p class="text-sm text-surface-500 dark:text-surface-400">Para quem está começando</p>
                </div>
                <div class="mb-6">
                    <span class="text-4xl font-black text-surface-900 dark:text-white">Grátis</span>
                    <span class="text-surface-500 dark:text-surface-400 text-sm ml-1">para sempre</span>
                </div>
                <ul class="space-y-3 mb-8">
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> {{ $free['wallets'] }} carteira <x-ui.help-hint text="Uma conta, cartão ou reserva para organizar seus lançamentos." /></li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> {{ $free['transactions_per_month'] }} transações/mês <x-ui.help-hint text="Receitas e despesas lançadas no mês. O contador reinicia todo mês." /></li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> {{ $free['goals'] }} metas financeiras</li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> {{ $free['categories'] }} categorias</li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> {{ $free['budgets_per_month'] }} orçamentos/mês <x-ui.help-hint text="Limites de gasto por categoria para acompanhar no mês." /></li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> Dashboard com resumo</li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> 7 Calculadoras financeiras</li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> Testes de perfil financeiro e investidor</li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> Cotação de moedas em tempo real</li>
                    <li class="flex items-start gap-2 text-sm text-surface-400 dark:text-surface-500"><i class="fas fa-times text-surface-300 dark:text-surface-600 mt-0.5" aria-hidden="true"></i> Simuladores financeiros</li>
                    <li class="flex items-start gap-2 text-sm text-surface-400 dark:text-surface-500"><i class="fas fa-times text-surface-300 dark:text-surface-600 mt-0.5" aria-hidden="true"></i> Relatórios com gráficos + CSV</li>
                    <li class="flex items-start gap-2 text-sm text-surface-400 dark:text-surface-500"><i class="fas fa-times text-surface-300 dark:text-surface-600 mt-0.5" aria-hidden="true"></i> Inteligência Artificial</li>
                    <li class="flex items-start gap-2 text-sm text-surface-400 dark:text-surface-500"><i class="fas fa-times text-surface-300 dark:text-surface-600 mt-0.5" aria-hidden="true"></i> Compartilhamento de carteiras</li>
                </ul>
                <a href="{{ $signupUrl }}" data-loading="Carregando..." class="block w-full text-center px-6 py-4 bg-surface-200 dark:bg-surface-700 hover:bg-surface-300 dark:hover:bg-surface-600 text-surface-900 dark:text-white font-bold rounded-xl transition">
                    <i class="fas fa-user-plus mr-2" aria-hidden="true"></i> Criar Conta Grátis
                </a>
            </div>

            @inject('pricing', \App\Interfaces\Services\PlanPricingInterface::class)
            <div class="bg-white dark:bg-surface-900 rounded-2xl p-8 border-2 border-primary-500 shadow-xl relative">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                    @if($pricing->isPromoActive())
                        <span class="inline-flex items-center px-4 py-1.5 bg-accent-500 text-white text-xs font-bold rounded-full shadow-lg"><i class="fas fa-tag mr-1" aria-hidden="true"></i> {{ $pricing->promoLabel() }} até {{ $pricing->promoEndsAt()->format('d/m/Y') }}</span>
                    @else
                        <span class="inline-flex items-center px-4 py-1.5 bg-primary-600 text-white text-xs font-bold rounded-full shadow-lg"><i class="fas fa-star mr-1" aria-hidden="true"></i> Recomendado</span>
                    @endif
                </div>
                <div class="mb-6 mt-2">
                    <h3 class="text-xl font-bold text-surface-900 dark:text-white mb-1">Profissional</h3>
                    <p class="text-sm text-surface-500 dark:text-surface-400">Para quem quer resultados reais</p>
                </div>
                <div class="mb-6">
                    @if($pricing->isPromoActive())
                        <span class="text-sm text-surface-400 line-through mr-2">{{ $pricing->formattedRegular() }}</span>
                    @endif
                    <span class="num text-4xl font-black text-primary-600">{{ $pricing->formattedCurrent() }}</span>
                    <span class="text-surface-500 dark:text-surface-400 text-sm">/mês</span>
                </div>
                <ul class="space-y-3 mb-8">
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> <span><strong>Tudo ilimitado</strong> (carteiras, transações, metas, categorias, orçamentos)</span> <x-ui.help-hint text="Sem limite de quantidade em carteiras, lançamentos, metas, categorias e orçamentos." /></li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> Compartilhamento de carteiras empresariais</li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> 3 Simuladores financeiros</li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> Relatórios com gráficos + exportação CSV e PDF</li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> 5 funcionalidades de Inteligência Artificial <x-ui.help-hint text="Análise financeira, análise de metas, plano por objetivo, parecer técnico e planejamento financeiro." /></li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> Acesso à API REST <x-ui.help-hint text="Integre seus dados com outros sistemas usando tokens pessoais." /></li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> 7 Calculadoras financeiras</li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> Testes de perfil financeiro e investidor</li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> Cotação de moedas em tempo real</li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> Dashboard completo</li>
                    <li class="flex items-start gap-2 text-sm text-surface-600 dark:text-surface-400"><i class="fas fa-check text-secondary-500 mt-0.5" aria-hidden="true"></i> Suporte prioritário</li>
                </ul>
                <a href="{{ $signupUrl }}" data-loading="Carregando..." class="block w-full text-center px-6 py-4 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl transition shadow-lg">
                    <i class="fas fa-rocket mr-2" aria-hidden="true"></i> Assinar o Profissional
                </a>
                <p class="text-xs text-surface-500 dark:text-surface-400 text-center mt-3"><i class="fas fa-shield-alt mr-1" aria-hidden="true"></i> Cancele a qualquer momento. Sem multa.</p>
            </div>
        </div>

        <div class="text-center mt-10">
            <p class="text-sm text-surface-500 dark:text-surface-400"><i class="fas fa-circle-info text-primary-500 mr-1" aria-hidden="true"></i> Comece no Básico sem cartão de crédito e faça upgrade só quando precisar. Dúvidas sobre os planos? Veja as <a href="#faq" class="text-primary-600 dark:text-primary-400 font-medium hover:underline">perguntas frequentes</a>.</p>
        </div>
    </div>
</section>

{{-- ============================================================= --}}
{{-- FAQ (answer-first, static content: no DB on "/")              --}}
{{-- ============================================================= --}}
<section id="faq" class="py-24 bg-surface-50 dark:bg-surface-800">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="inline-flex items-center px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-full mb-4">
                <i class="fas fa-circle-question mr-1.5" aria-hidden="true"></i> Dúvidas
            </span>
            <h2 class="font-display text-3xl md:text-4xl font-semibold text-surface-900 dark:text-white mb-4">Perguntas frequentes</h2>
            <p class="text-lg text-surface-600 dark:text-surface-400">Respostas diretas para as dúvidas mais comuns sobre o Finestro.</p>
        </div>
        <div class="space-y-3">
            @foreach(\App\Support\Landing\LandingContent::faq() as $item)
            <details class="group bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-700 open:shadow-soft">
                <summary class="flex items-center justify-between gap-4 cursor-pointer list-none px-6 py-5 rounded-2xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <h3 class="text-base font-semibold text-surface-900 dark:text-white">{{ $item['question'] }}</h3>
                    <i class="fas fa-chevron-down text-surface-400 transition-transform group-open:rotate-180" aria-hidden="true"></i>
                </summary>
                <p class="px-6 pb-5 text-surface-600 dark:text-surface-400 leading-relaxed">{{ $item['answer'] }}</p>
            </details>
            @endforeach
        </div>
        <p class="mt-8 text-center text-sm text-surface-500 dark:text-surface-400">
            Não achou sua resposta? Visite a <a href="{{ route('faq') }}" class="text-primary-600 dark:text-primary-400 font-medium hover:underline">Central de Ajuda</a> ou <a href="{{ route('contact') }}" class="text-primary-600 dark:text-primary-400 font-medium hover:underline">fale com a gente</a>.
        </p>
    </div>
</section>

{{-- ============================================================= --}}
{{-- CONTATO (from pages/contact) — direct channels, no form       --}}
{{-- ============================================================= --}}
<section id="contato" class="py-24 bg-white dark:bg-surface-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <span class="inline-flex items-center px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-full mb-4">
                <i class="fas fa-envelope mr-1.5" aria-hidden="true"></i> Contato
            </span>
            <h2 class="font-display text-3xl md:text-4xl font-semibold text-surface-900 dark:text-white mb-4">Entre em Contato</h2>
            <p class="text-lg text-surface-600 dark:text-surface-400 max-w-2xl mx-auto">
                Tem dúvidas, sugestões ou precisa de ajuda? Fale com a gente pelo WhatsApp ou e-mail. Respondemos em até 24h úteis.
            </p>
        </div>

        @include('components.contact.channels')
    </div>
</section>

{{-- ============================================================= --}}
{{-- CTA (Finestro Pro). The Grupo 3W Online CTA is rendered by the  --}}
{{-- landing layout (cta/agency component, variant "landing").     --}}
{{-- ============================================================= --}}
@include('components.cta.main')

@endsection
