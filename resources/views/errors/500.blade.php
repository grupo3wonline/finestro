@extends('layouts.landing')

@section('title', 'Erro Interno')

@section('content')
<section class="py-20">
    <div class="max-w-lg mx-auto px-4 text-center">
        <div class="text-8xl font-bold text-orange-600 mb-4">500</div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Erro Interno do Servidor</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
            Ocorreu um erro inesperado. Nossa equipe já foi notificada. Tente novamente em alguns instantes.
        </p>
        <a href="{{ route('home') }}" class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition">
            <i class="fas fa-home mr-2"></i> Página Inicial
        </a>
    </div>
</section>
@endsection
