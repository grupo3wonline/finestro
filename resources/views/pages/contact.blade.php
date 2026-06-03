@extends('layouts.landing')

@section('title', 'Contato')
@section('meta_description', 'Entre em contato com a equipe Finestro. Estamos prontos para ajudar você.')

@section('content')
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8 text-sm" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
                <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
                <li class="text-gray-700 dark:text-gray-300 font-medium">Contato</li>
            </ol>
        </nav>

        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">Entre em Contato</h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Tem dúvidas, sugestões ou precisa de ajuda? Fale conosco pelos canais abaixo. Estamos prontos para atender você.
            </p>
        </div>

        <div class="max-w-3xl mx-auto">
            <!-- Contact Cards -->
            <div class="grid sm:grid-cols-2 gap-6 mb-10">
                <!-- WhatsApp -->
                <a href="https://wa.me/5541996924829" target="_blank" rel="noopener" class="group bg-white dark:bg-gray-900 rounded-2xl p-8 shadow-sm border border-gray-200 dark:border-gray-700 hover:border-green-300 dark:hover:border-green-700 hover:shadow-md transition-all duration-300 text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-lg shadow-green-500/20 group-hover:scale-110 transition-transform duration-300">
                        <i class="fab fa-whatsapp text-white text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">WhatsApp</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">Atendimento rápido e direto</p>
                    <span class="inline-flex items-center text-green-600 dark:text-green-400 font-semibold">
                        <i class="fas fa-phone-alt mr-2"></i> (41) 99692-4829
                    </span>
                </a>

                <!-- Email -->
                <a href="mailto:contato@3wonline.com.br" class="group bg-white dark:bg-gray-900 rounded-2xl p-8 shadow-sm border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-700 hover:shadow-md transition-all duration-300 text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-lg shadow-primary-500/20 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-envelope text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">E-mail</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">Para dúvidas e solicitações</p>
                    <span class="inline-flex items-center text-primary-600 dark:text-primary-400 font-semibold">
                        <i class="fas fa-at mr-2"></i> contato@3wonline.com.br
                    </span>
                </a>
            </div>

            <!-- Additional Info -->
            <div class="grid sm:grid-cols-2 gap-6 mb-10">
                <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-secondary-100 dark:bg-secondary-900/30 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-secondary-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Horário de Atendimento</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Seg-Sex: 9h às 18h</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <i class="fas fa-headset text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Tempo de Resposta</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Até 24h úteis</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Developer Info -->
            <div class="bg-gradient-to-br from-gray-900 to-gray-800 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-8 md:p-10 text-center border border-gray-700">
                <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-accent-500 rounded-xl flex items-center justify-center mx-auto mb-5 shadow-lg">
                    <i class="fas fa-code text-white text-xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-700 mb-3">Desenvolvido pelo Grupo 3W Online</h3>
                <p class="text-gray-500 mb-6 max-w-lg mx-auto">
                    Somos especializados no desenvolvimento de sites e sistemas personalizados sob demanda. Transformamos ideias em soluções digitais de alto impacto.
                </p>
                <a href="https://www.3wonline.com.br" target="_blank" rel="noopener" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary-600 to-accent-600 hover:from-primary-700 hover:to-accent-700 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                    <i class="fas fa-external-link-alt mr-2"></i> Visite nosso site
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
@include('components.cta.main')
@endsection
