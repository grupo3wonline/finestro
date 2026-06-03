@extends('layouts.landing')

@section('title', 'Sobre')
@section('meta_description', 'Conheça o Finestro - plataforma de gestão financeira pessoal desenvolvida para simplificar o controle do seu dinheiro.')

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

        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-6">Sobre o Finestro</h1>
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-4">
                    O <strong>Finestro</strong> nasceu da necessidade de ter uma ferramenta simples, segura e eficiente para o controle financeiro pessoal.
                </p>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Desenvolvido com tecnologia de ponta, nossa plataforma permite que você organize suas finanças de forma intuitiva, acompanhe seus gastos em tempo real e defina metas que realmente fazem diferença na sua vida.
                </p>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Acreditamos que o controle financeiro não precisa ser complicado. Por isso, criamos uma experiência que combina simplicidade com funcionalidades poderosas.
                </p>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <i class="fas fa-check-circle text-secondary-500"></i> Segurança LGPD
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <i class="fas fa-check-circle text-secondary-500"></i> Dados criptografados
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
                            <p class="text-sm text-gray-600 dark:text-gray-400">Democratizar o acesso à educação e controle financeiro para todos os brasileiros.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-secondary-100 dark:bg-secondary-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-heart text-secondary-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Nossa Missão</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Oferecer ferramentas práticas que transformem a relação das pessoas com o dinheiro.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-star text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Nossos Valores</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Transparência, segurança, simplicidade e compromisso com o usuário.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
@include('components.cta.main')
@endsection
