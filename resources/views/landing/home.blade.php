@extends('layouts.landing')

@section('title', 'Gestão Financeira Pessoal Inteligente')
@section('meta_description', 'Finestro - Controle suas finanças pessoais de forma simples, inteligente e segura. Carteiras, transações, metas e simuladores financeiros.')

@section('content')
<!-- Hero Section -->
<section class="relative overflow-hidden bg-gradient-to-br from-surface-900 via-primary-950 to-surface-900 text-white min-h-[90vh] flex items-center">
    <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute top-1/4 -left-20 w-96 h-96 bg-primary-500/10 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-1/4 -right-20 w-[500px] h-[500px] bg-secondary-500/10 rounded-full blur-3xl animate-float" style="animation-delay: -3s"></div>
        <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:60px_60px]"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-32 relative z-10 w-full">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full border border-white/10 mb-8">
                    <span class="w-2 h-2 bg-secondary-400 rounded-full animate-pulse"></span>
                    <span class="text-sm text-gray-300">Plataforma de gestão financeira pessoal</span>
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold leading-[1.1] mb-6 tracking-tight">
                    Controle suas
                    <span class="bg-gradient-to-r from-primary-400 via-secondary-400 to-accent-400 bg-clip-text text-transparent"> finanças </span>
                    com inteligência
                </h1>
                <p class="text-lg sm:text-xl text-gray-300 mb-10 max-w-xl leading-relaxed">
                    Organize carteiras, acompanhe transações e alcance suas metas financeiras. Tudo em um só lugar, de forma simples e segura.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 mb-8">
                    <a href="{{ route('register') }}" class="group inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-bold rounded-2xl transition-all duration-300 shadow-lg shadow-primary-500/25 hover:shadow-xl hover:-translate-y-0.5">
                        <i class="fas fa-rocket mr-2"></i> Começar Grátis
                    </a>
                    <a href="#features" class="inline-flex items-center justify-center px-8 py-4 bg-white/5 backdrop-blur-sm border border-white/10 text-white font-medium rounded-2xl hover:bg-white/10 transition-all duration-300">
                        <i class="fas fa-play-circle mr-2"></i> Saiba Mais
                    </a>
                </div>
                <div class="flex items-center gap-6 text-sm text-gray-400">
                    <span class="flex items-center gap-1.5"><i class="fas fa-check-circle text-secondary-400"></i> Sem cartão</span>
                    <span class="flex items-center gap-1.5"><i class="fas fa-check-circle text-secondary-400"></i> Setup em 2 min</span>
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
                                        <i class="fas fa-arrow-up text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-400">Receitas do mês</p>
                                        <p class="text-2xl font-bold text-white">R$ 8.450</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-secondary-500/20 text-secondary-300 text-sm font-semibold rounded-lg">+12%</span>
                            </div>
                            <div class="flex items-center justify-between bg-white/5 rounded-2xl p-5 border border-white/5">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-red-600 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-arrow-down text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-400">Despesas do mês</p>
                                        <p class="text-2xl font-bold text-white">R$ 3.280</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-red-500/20 text-red-300 text-sm font-semibold rounded-lg">-5%</span>
                            </div>
                            <div class="flex items-center justify-between bg-white/5 rounded-2xl p-5 border border-white/5">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-primary-400 to-accent-500 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-piggy-bank text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-400">Economia</p>
                                        <p class="text-2xl font-bold text-white">R$ 5.170</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-primary-500/20 text-primary-300 text-sm font-semibold rounded-lg">61%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-24 bg-white dark:bg-surface-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="inline-flex items-center px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-full mb-4">
                <i class="fas fa-star mr-1.5"></i> Funcionalidades
            </span>
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                Tudo que você precisa para suas finanças
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Funcionalidades pensadas para simplificar o controle do seu dinheiro e ajudar você a alcançar seus objetivos.
            </p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            <div class="group bg-gray-50 dark:bg-surface-800 rounded-2xl p-7 border border-gray-100 dark:border-surface-700 hover:border-primary-200 dark:hover:border-primary-800 hover:shadow-soft transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-primary-500/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-wallet text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Múltiplas Carteiras</h3>
                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Organize suas contas bancárias, cartões e investimentos em carteiras separadas com saldos em tempo real.</p>
            </div>
            <div class="group bg-gray-50 dark:bg-surface-800 rounded-2xl p-7 border border-gray-100 dark:border-surface-700 hover:border-secondary-200 dark:hover:border-secondary-800 hover:shadow-soft transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-secondary-500 to-secondary-600 rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-secondary-500/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-exchange-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Controle de Transações</h3>
                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Registre receitas e despesas, categorize e acompanhe para onde vai cada centavo do seu dinheiro.</p>
            </div>
            <div class="group bg-gray-50 dark:bg-surface-800 rounded-2xl p-7 border border-gray-100 dark:border-surface-700 hover:border-accent-200 dark:hover:border-accent-800 hover:shadow-soft transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-accent-500 to-accent-600 rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-accent-500/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-bullseye text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Metas Financeiras</h3>
                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Defina objetivos, acompanhe o progresso e celebre cada conquista no caminho da liberdade financeira.</p>
            </div>
            <div class="group bg-gray-50 dark:bg-surface-800 rounded-2xl p-7 border border-gray-100 dark:border-surface-700 hover:border-orange-200 dark:hover:border-orange-800 hover:shadow-soft transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-orange-500/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-chart-pie text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Relatórios Visuais</h3>
                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Gráficos e dashboards intuitivos para visualizar sua saúde financeira de forma clara e objetiva.</p>
            </div>
            <div class="group bg-gray-50 dark:bg-surface-800 rounded-2xl p-7 border border-gray-100 dark:border-surface-700 hover:border-red-200 dark:hover:border-red-800 hover:shadow-soft transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-600 rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-red-500/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Segurança Total</h3>
                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Seus dados são protegidos com criptografia e conformidade LGPD. Apenas você acessa suas informações.</p>
            </div>
            <div class="group bg-gray-50 dark:bg-surface-800 rounded-2xl p-7 border border-gray-100 dark:border-surface-700 hover:border-teal-200 dark:hover:border-teal-800 hover:shadow-soft transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-teal-500 to-teal-600 rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-teal-500/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-mobile-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Acesso em Qualquer Lugar</h3>
                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Interface responsiva e otimizada para celular, tablet e desktop. Suas finanças sempre ao alcance.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-24 bg-gray-50 dark:bg-surface-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">Como funciona?</h2>
            <p class="text-lg text-gray-600 dark:text-gray-400">Em 3 passos simples você já está no controle das suas finanças.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8 relative">
            <div class="hidden md:block absolute top-16 left-1/4 right-1/4 h-0.5 bg-gradient-to-r from-primary-200 via-secondary-200 to-accent-200 dark:from-primary-800 dark:via-secondary-800 dark:to-accent-800"></div>
            <div class="text-center relative">
                <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-lg shadow-primary-500/20 relative z-10">
                    <span class="text-2xl font-bold text-white">1</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Crie sua conta</h3>
                <p class="text-gray-600 dark:text-gray-400">Cadastro rápido e gratuito. Sem burocracia, sem cartão de crédito.</p>
            </div>
            <div class="text-center relative">
                <div class="w-16 h-16 bg-gradient-to-br from-secondary-500 to-secondary-600 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-lg shadow-secondary-500/20 relative z-10">
                    <span class="text-2xl font-bold text-white">2</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Configure suas carteiras</h3>
                <p class="text-gray-600 dark:text-gray-400">Adicione suas contas e comece a registrar suas transações diárias.</p>
            </div>
            <div class="text-center relative">
                <div class="w-16 h-16 bg-gradient-to-br from-accent-500 to-accent-600 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-lg shadow-accent-500/20 relative z-10">
                    <span class="text-2xl font-bold text-white">3</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Acompanhe e cresça</h3>
                <p class="text-gray-600 dark:text-gray-400">Visualize relatórios, defina metas e tome decisões financeiras mais inteligentes.</p>
            </div>
        </div>
    </div>
</section>

<!-- Tips Section -->
<section class="py-24 bg-white dark:bg-surface-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-lightbulb text-yellow-500 mr-2"></i> Dicas Financeiras
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-400">Orientações práticas para melhorar sua vida financeira.</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-gradient-to-br from-primary-50 to-white dark:from-surface-800 dark:to-surface-800 rounded-2xl p-6 border border-primary-100 dark:border-surface-700">
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-piggy-bank text-xl text-primary-600"></i>
                </div>
                <h4 class="font-bold text-gray-900 dark:text-white mb-2">Regra 50-30-20</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Destine 50% para necessidades, 30% para desejos e 20% para poupança.</p>
            </div>
            <div class="bg-gradient-to-br from-secondary-50 to-white dark:from-surface-800 dark:to-surface-800 rounded-2xl p-6 border border-secondary-100 dark:border-surface-700">
                <div class="w-12 h-12 bg-secondary-100 dark:bg-secondary-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-calendar-check text-xl text-secondary-600"></i>
                </div>
                <h4 class="font-bold text-gray-900 dark:text-white mb-2">Registre Tudo</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Anote cada gasto, por menor que seja. Pequenos valores somam grandes montantes.</p>
            </div>
            <div class="bg-gradient-to-br from-accent-50 to-white dark:from-surface-800 dark:to-surface-800 rounded-2xl p-6 border border-accent-100 dark:border-surface-700">
                <div class="w-12 h-12 bg-accent-100 dark:bg-accent-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-crosshairs text-xl text-accent-600"></i>
                </div>
                <h4 class="font-bold text-gray-900 dark:text-white mb-2">Defina Metas</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Ter objetivos claros aumenta em 42% suas chances de economizar.</p>
            </div>
            <div class="bg-gradient-to-br from-red-50 to-white dark:from-surface-800 dark:to-surface-800 rounded-2xl p-6 border border-red-100 dark:border-surface-700">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-shield-alt text-xl text-red-600"></i>
                </div>
                <h4 class="font-bold text-gray-900 dark:text-white mb-2">Reserva de Emergência</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Mantenha de 3 a 6 meses de despesas guardados para imprevistos.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-24 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900"></div>
    <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:40px_40px]"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">Pronto para transformar suas finanças?</h2>
        <p class="text-lg text-primary-100 mb-4 max-w-2xl mx-auto">Junte-se a milhares de pessoas que já estão no controle do seu dinheiro. Comece grátis e desbloqueie tudo com o Plano Profissional.</p>

        <!-- Urgency pricing highlight -->
        <div class="inline-flex items-center bg-white/10 backdrop-blur rounded-full px-6 py-2 mb-6 border border-white/20">
            <span class="text-yellow-300 font-bold text-sm mr-2"><i class="fas fa-fire mr-1"></i> OFERTA LIMITADA:</span>
            <span class="text-white/70 line-through text-sm mr-2">R$ 41,58</span>
            <span class="text-white font-black text-lg">R$ 24,90/mês</span>
            <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full ml-2">-40%</span>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <a href="{{ route('register') }}" class="inline-flex items-center px-10 py-5 bg-white text-primary-700 font-bold rounded-2xl hover:bg-gray-50 transition-all duration-300 shadow-xl hover:shadow-2xl hover:-translate-y-0.5 text-lg">
                <i class="fas fa-user-plus mr-2"></i> Criar Minha Conta Grátis
            </a>
        </div>
        <p class="text-xs text-primary-200 mt-4"><i class="fas fa-clock mr-1"></i> Preço promocional por tempo limitado. Garanta antes que volte para R$ 41,58/mês.</p>
    </div>
</section>

<!-- CTA 3W Online -->
<section class="py-20 bg-gray-50 dark:bg-surface-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="bg-white dark:bg-surface-900 rounded-3xl p-10 md:p-14 border border-gray-200 dark:border-surface-700 shadow-soft">
            <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-accent-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                <i class="fas fa-code text-white text-2xl"></i>
            </div>
            <h3 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4">Precisa de um site ou sistema personalizado?</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-xl mx-auto text-lg">O <strong class="text-gray-900 dark:text-white">Grupo 3W Online</strong> desenvolve sites e sistemas sob demanda com tecnologia de ponta, design moderno e foco em resultados.</p>
            <a href="https://www.3wonline.com.br" target="_blank" rel="noopener" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-primary-600 to-accent-600 hover:from-primary-700 hover:to-accent-700 text-white font-bold rounded-2xl transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                <i class="fas fa-external-link-alt mr-2"></i> Conheça o Grupo 3W Online
            </a>
        </div>
    </div>
</section>
@endsection
