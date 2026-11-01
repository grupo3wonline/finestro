@extends('layouts.landing')

@section('title', 'Página Não Encontrada')
@section('agency_cta_variant', 'error')

@section('content')
<section class="relative overflow-hidden min-h-[70vh] flex items-center">
    <div class="absolute inset-0 -z-10" aria-hidden="true">
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-96 h-96 bg-primary-500/10 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-lg mx-auto px-4 py-16 text-center">
        <div class="w-20 h-20 mx-auto mb-6 rounded-3xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
            <i class="fas fa-compass text-4xl text-primary-600 dark:text-primary-400" aria-hidden="true"></i>
        </div>
        <div class="font-display text-8xl font-semibold text-gradient mb-2" aria-hidden="true">404</div>
        <h1 class="text-2xl font-bold text-surface-900 dark:text-white mb-3">Página não encontrada</h1>
        <p class="text-surface-600 dark:text-surface-400 mb-6">
            A página que você procura não existe ou foi movida. Verifique o endereço ou volte para o início.
        </p>
        <x-ui.tip icon="fa-lightbulb" class="mb-8 text-left">
            Se você chegou aqui por um link salvo, ele pode ser antigo. Use o menu acima ou a Central de Ajuda para encontrar o que precisa.
        </x-ui.tip>
        @include('errors.partials.actions')
    </div>
</section>
@endsection
