@extends('layouts.landing')

@section('title', 'Acesso Negado')

@section('content')
<section class="py-20">
    <div class="max-w-lg mx-auto px-4 text-center">
        <div class="text-8xl font-bold text-red-600 mb-4">403</div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Acesso Negado</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
            Você não tem permissão para acessar esta página. Se acredita que isso é um erro, entre em contato com o suporte.
        </p>
        <a href="{{ route('home') }}" class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition">
            <i class="fas fa-home mr-2"></i> Página Inicial
        </a>
    </div>
</section>
@endsection
