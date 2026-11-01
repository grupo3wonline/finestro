@extends('layouts.landing')

@section('title', 'Em Manutenção')
@section('agency_cta_variant', 'error')

@section('content')
<section class="relative overflow-hidden min-h-[70vh] flex items-center">
    <div class="absolute inset-0 -z-10" aria-hidden="true">
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-96 h-96 bg-secondary-500/10 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-lg mx-auto px-4 py-16 text-center">
        <div class="w-20 h-20 mx-auto mb-6 rounded-3xl bg-secondary-50 dark:bg-secondary-900/30 flex items-center justify-center">
            <i class="fas fa-screwdriver-wrench text-4xl text-secondary-600 dark:text-secondary-400" aria-hidden="true"></i>
        </div>
        <div class="font-display text-8xl font-semibold text-secondary-600 mb-2" aria-hidden="true">503</div>
        <h1 class="text-2xl font-bold text-surface-900 dark:text-white mb-3">Em manutenção</h1>
        <p class="text-surface-600 dark:text-surface-400 mb-6">
            Estamos realizando uma manutenção para melhorar sua experiência ou o serviço está temporariamente indisponível. Voltaremos em breve.
        </p>
        <x-ui.tip icon="fa-lightbulb" class="mb-8 text-left">
            Aguarde alguns minutos e recarregue a página. Se você estava preenchendo um formulário, confira os dados antes de enviar de novo.
        </x-ui.tip>
        @include('errors.partials.actions', ['reload' => true])
    </div>
</section>
@endsection
