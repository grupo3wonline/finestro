@extends('layouts.landing')

@section('title', 'Muitas Tentativas')
@section('agency_cta_variant', 'error')

@section('content')
<section class="relative overflow-hidden min-h-[70vh] flex items-center">
    <div class="absolute inset-0 -z-10" aria-hidden="true">
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-96 h-96 bg-red-500/10 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-lg mx-auto px-4 py-16 text-center">
        <div class="w-20 h-20 mx-auto mb-6 rounded-3xl bg-red-50 dark:bg-red-900/30 flex items-center justify-center">
            <i class="fas fa-gauge-high text-4xl text-red-600 dark:text-red-400" aria-hidden="true"></i>
        </div>
        <div class="font-display text-8xl font-semibold text-red-600 mb-2" aria-hidden="true">429</div>
        <h1 class="text-2xl font-bold text-surface-900 dark:text-white mb-3">Muitas tentativas</h1>
        <p class="text-surface-600 dark:text-surface-400 mb-6">
            Recebemos muitas solicitações em pouco tempo. Para proteger sua conta, aguarde alguns minutos antes de tentar de novo.
        </p>
        <x-ui.tip icon="fa-lightbulb" class="mb-8 text-left">
            Evite clicar várias vezes no mesmo botão. Se esqueceu a senha, use "Esqueci minha senha" na tela de login em vez de tentar várias combinações.
        </x-ui.tip>
        @include('errors.partials.actions')
    </div>
</section>
@endsection
