@extends('layouts.landing')

@section('title', 'Em Manutenção')

@section('content')
<section class="py-20">
    <div class="max-w-lg mx-auto px-4 text-center">
        <div class="text-8xl font-bold text-yellow-600 mb-4">503</div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Em Manutenção</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
            Estamos realizando uma manutenção programada para melhorar sua experiência. Voltaremos em breve!
        </p>
        <div class="inline-flex items-center px-4 py-2 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-lg text-sm">
            <i class="fas fa-clock mr-2"></i> Previsão de retorno: em breve
        </div>
    </div>
</section>
@endsection
