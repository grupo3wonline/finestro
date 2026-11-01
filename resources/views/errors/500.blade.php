@extends('layouts.landing')

@section('title', 'Erro Interno')
@section('agency_cta_variant', 'error')

@section('content')
<section class="relative overflow-hidden min-h-[70vh] flex items-center">
    <div class="absolute inset-0 -z-10" aria-hidden="true">
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-96 h-96 bg-accent-500/10 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-lg mx-auto px-4 py-16 text-center">
        <div class="w-20 h-20 mx-auto mb-6 rounded-3xl bg-accent-50 dark:bg-accent-900/30 flex items-center justify-center">
            <i class="fas fa-triangle-exclamation text-4xl text-accent-600 dark:text-accent-400" aria-hidden="true"></i>
        </div>
        <div class="font-display text-8xl font-semibold text-accent-500 mb-2" aria-hidden="true">500</div>
        <h1 class="text-2xl font-bold text-surface-900 dark:text-white mb-3">Erro interno do servidor</h1>
        <p class="text-surface-600 dark:text-surface-400 mb-6">
            Ocorreu um erro inesperado do nosso lado e ele foi registrado para análise. Tente novamente em alguns instantes.
        </p>
        <x-ui.tip icon="fa-lightbulb" class="mb-8 text-left">
            Se o problema continuar, fale com o suporte pela página de <a href="{{ route('contact') }}" class="font-semibold underline hover:no-underline">Contato</a> e conte o que você estava fazendo e o horário aproximado.
        </x-ui.tip>
        @include('errors.partials.actions', ['reload' => true])
    </div>
</section>
@endsection
