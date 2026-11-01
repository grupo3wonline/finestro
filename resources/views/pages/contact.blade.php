@extends('layouts.landing')

@section('title', 'Contato')
@section('meta_description', 'Fale com a equipe do Finestro e do Grupo 3W Online pelo WhatsApp (41) 99692-4829 ou pelo e-mail contato@3wonline.com.br. Resposta em até 24h úteis.')

@section('content')
<section class="py-12 md:py-16 bg-white dark:bg-surface-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto mb-8">
            <x-ui.breadcrumb :items="[['label' => 'Contato']]" />
        </div>

        <div class="text-center mb-12">
            <span class="inline-flex items-center px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-full mb-4">
                <i class="fas fa-envelope mr-1.5" aria-hidden="true"></i> Contato
            </span>
            <h1 class="font-display text-3xl md:text-5xl font-semibold text-surface-900 dark:text-white mb-4">Fale com a gente</h1>
            <p class="text-lg text-surface-600 dark:text-surface-400 max-w-2xl mx-auto">
                Tem dúvidas sobre o Finestro, quer sugerir uma melhoria ou precisa de um site ou sistema sob medida? Escolha o canal que preferir: atendemos pelo WhatsApp e por e-mail, sem formulários.
            </p>
        </div>

        @include('components.contact.channels')

        <div class="mt-10 max-w-3xl mx-auto">
            <x-ui.tip icon="fa-lightbulb">
                <strong>Dica:</strong> para agilizar o atendimento, informe o e-mail da sua conta e descreva em poucas linhas o que aconteceu. Muitas dúvidas já têm resposta na <a href="{{ route('faq') }}" class="font-semibold underline hover:no-underline">Central de Ajuda</a>.
            </x-ui.tip>
        </div>

        @guest
        <div class="mt-10 text-center">
            <a href="{{ route('register', \App\Support\Utm::fromRequest(request())) }}" data-loading="Carregando..." class="inline-flex items-center justify-center px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-2xl transition shadow-lg">
                <i class="fas fa-user-plus mr-2" aria-hidden="true"></i> Ainda não usa o Finestro? Crie sua conta grátis
            </a>
        </div>
        @endguest
    </div>
</section>
@endsection
