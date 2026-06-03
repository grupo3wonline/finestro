@extends('layouts.guest')

@section('title', 'Entrar')

@section('content')
<h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-6">Entrar</h2>
<p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">Acesse sua conta para gerenciar suas finanças.</p>

@if(session('status'))
<div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-400">
    {{ session('status') }}
</div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="off" required autofocus
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
            placeholder="seu@email.com">
        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Senha</label>
        <input type="password" name="password" id="password" autocomplete="off" required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
            placeholder="Sua senha">
        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <input type="checkbox" name="remember" class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
            Lembrar-me
        </label>
        @if (Route::has('password.request'))
        <a href="{{ route('password.request') }}" class="text-sm text-primary-600 hover:underline">Esqueceu a senha?</a>
        @endif
    </div>

    <button type="submit" class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition">
        <i class="fas fa-sign-in-alt mr-2"></i> Entrar
    </button>

    <p class="text-center text-sm text-gray-500 dark:text-gray-400">
        Não tem conta? <a href="{{ route('register') }}" class="text-primary-600 hover:underline font-medium">Criar conta grátis</a>
    </p>
</form>
@endsection
