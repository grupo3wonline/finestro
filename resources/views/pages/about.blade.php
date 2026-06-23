@extends('layouts.landing')

@section('title', 'Sobre o Finestro')
@section('meta_description', 'Conheça o Finestro - plataforma de gestão financeira pessoal com IA, desenvolvida pelo Grupo 3W Online. Segurança LGPD, dados criptografados e tecnologia de ponta.')

@section('content')
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8 text-sm" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
                <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
                <li class="text-gray-700 dark:text-gray-300 font-medium">Sobre</li>
            </ol>
        </nav>

        <!-- Hero About -->
        <div class="grid md:grid-cols-2 gap-12 items-center mb-16">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-6">Sobre o Finestro</h1>
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-4">
                    O <strong>Finestro</strong> é uma plataforma de gestão financeira pessoal e empresarial desenvolvida para transformar a forma como você controla seu dinheiro.
                </p>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Combinamos tecnologia de ponta com inteligência artificial para oferecer uma experiência completa: desde o controle básico de receitas e despesas até análises financeiras personalizadas e simulações de investimentos.
                </p>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Nossa missão é democratizar o acesso à educação financeira e dar ferramentas práticas para que qualquer pessoa possa prosperar financeiramente.
                </p>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <i class="fas fa-check-circle text-secondary-500"></i> Conformidade LGPD
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <i class="fas fa-check-circle text-secondary-500"></i> Dados criptografados
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <i class="fas fa-check-circle text-secondary-500"></i> IA integrada
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <i class="fas fa-check-circle text-secondary-500"></i> Suporte dedicado
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-8 shadow-lg">
                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-eye text-primary-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Nossa Visão</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Ser a principal plataforma de gestão financeira pessoal do Brasil, acessível a todos que desejam prosperar.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-secondary-100 dark:bg-secondary-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-heart text-secondary-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Nossa Missão</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Oferecer ferramentas inteligentes e acessíveis que transformem a relação das pessoas com o dinheiro, promovendo educação e controle financeiro.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-star text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Nossos Valores</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Transparência, segurança, inovação, simplicidade e compromisso com o sucesso financeiro do usuário.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Technology Section -->
<section class="py-16 bg-white dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Tecnologia de Ponta</h2>
            <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Desenvolvido com as melhores tecnologias do mercado para garantir performance, segurança e escalabilidade.
            </p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fab fa-laravel text-3xl text-red-600"></i>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">Laravel Framework</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Framework PHP robusto e moderno, utilizado por milhões de desenvolvedores ao redor do mundo.</p>
            </div>
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-robot text-3xl text-purple-600"></i>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">Inteligência Artificial</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">IA avançada para análises financeiras, planos personalizados e insights inteligentes sobre suas finanças.</p>
            </div>
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-lock text-3xl text-green-600"></i>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">Criptografia Avançada</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Dados protegidos com criptografia de ponta a ponta. Apenas você tem acesso às suas informações financeiras.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Como Funciona</h2>
            <p class="text-lg text-gray-600 dark:text-gray-400">Em 3 passos simples você já está no controle das suas finanças.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8 relative">
            <div class="hidden md:block absolute top-12 left-1/4 right-1/4 h-0.5 bg-gradient-to-r from-primary-300 via-secondary-300 to-accent-300 dark:from-primary-700 dark:via-secondary-700 dark:to-accent-700"></div>
            <div class="text-center relative">
                <div class="w-14 h-14 bg-primary-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg relative z-10">
                    <span class="text-xl font-bold text-white">1</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Cadastre-se</h3>
                <p class="text-gray-600 dark:text-gray-400">Crie sua conta gratuita em menos de 2 minutos. Sem cartão de crédito, sem burocracia.</p>
            </div>
            <div class="text-center relative">
                <div class="w-14 h-14 bg-secondary-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg relative z-10">
                    <span class="text-xl font-bold text-white">2</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Organize</h3>
                <p class="text-gray-600 dark:text-gray-400">Configure suas carteiras, registre transações e defina metas. Use calculadoras e simuladores gratuitamente.</p>
            </div>
            <div class="text-center relative">
                <div class="w-14 h-14 bg-accent-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg relative z-10">
                    <span class="text-xl font-bold text-white">3</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Prospere</h3>
                <p class="text-gray-600 dark:text-gray-400">Acompanhe relatórios, use IA para insights e tome decisões financeiras mais inteligentes.</p>
            </div>
        </div>
    </div>
</section>

<!-- Team / Company -->
<section class="py-16 bg-white dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Desenvolvido pelo Grupo 3W Online</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    O Finestro é um produto do <strong>Grupo 3W Online</strong>, empresa especializada em desenvolvimento de sites e sistemas sob demanda com tecnologia de ponta.
                </p>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Com expertise em projetos digitais e foco em resultados, nossa equipe combina design moderno, performance e segurança para entregar soluções que realmente fazem a diferença.
                </p>
                <a href="https://www.3wonline.com.br" target="_blank" rel="noopener" class="inline-flex items-center text-primary-600 hover:text-primary-700 font-semibold">
                    <i class="fas fa-external-link-alt mr-2"></i> Conheça o Grupo 3W Online
                </a>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8">
                <h3 class="font-bold text-gray-900 dark:text-white mb-6 text-lg">Nossos Compromissos</h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-shield-alt text-green-500 mt-1"></i>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">Segurança LGPD</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Totalmente em conformidade com a Lei Geral de Proteção de Dados.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-user-lock text-blue-500 mt-1"></i>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">Privacidade Total</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Cada usuário acessa exclusivamente seus próprios dados.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-database text-purple-500 mt-1"></i>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">Dados Criptografados</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Informações protegidas com criptografia de ponta a ponta.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-trash-alt text-red-500 mt-1"></i>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">Direito ao Esquecimento</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Você pode solicitar a exclusão total dos seus dados a qualquer momento.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics -->
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Finestro em Números</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 text-center shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="text-3xl font-bold text-primary-600 mb-1">+500</div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Usuários ativos</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 text-center shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="text-3xl font-bold text-secondary-600 mb-1">+10.000</div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Transações registradas</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 text-center shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="text-3xl font-bold text-accent-600 mb-1">+1.200</div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Metas criadas</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 text-center shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="text-3xl font-bold text-purple-600 mb-1">99,9%</div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Disponibilidade</p>
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
