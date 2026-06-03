@extends('layouts.guest')

@section('title', 'Verificar E-mail')

@section('content')
<h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-4">Verificar E-mail</h2>
<p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">
    Obrigado por se cadastrar! Antes de começar, verifique seu e-mail clicando no link que enviamos. Se não recebeu, clique abaixo para reenviar.
</p>

@if (session('status') == 'verification-link-sent')
<div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-400">
    Um novo link de verificação foi enviado para o e-mail informado no cadastro.
</div>
@endif

<div class="space-y-4">
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition">
            <i class="fas fa-envelope mr-2"></i> Reenviar E-mail de Verificação
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="w-full py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
            <i class="fas fa-sign-out-alt mr-1"></i> Sair
        </button>
    </form>
</div>
@endsection
