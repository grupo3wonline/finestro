@extends('layouts.landing')

@section('title', 'Página Não Encontrada')

@section('content')
<section class="py-20">
    <div class="max-w-lg mx-auto px-4 text-center">
        <div class="text-8xl font-bold text-primary-600 mb-4">404</div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Página Não Encontrada</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
            A página que você está procurando não existe ou foi movida. Verifique o endereço ou volte para a página inicial.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition">
                <i class="fas fa-home mr-2"></i> Página Inicial
            </a>
            <a href="javascript:history.back()" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
    </div>
</section>
@endsection
