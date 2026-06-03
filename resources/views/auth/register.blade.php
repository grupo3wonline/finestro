@extends('layouts.guest')

@section('title', 'Criar Conta')

@section('content')
<h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-6">Criar Conta</h2>
<p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">Preencha os dados abaixo para criar sua conta gratuita.</p>

<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome Completo</label>
        <input type="text" name="name" id="name" value="{{ old('name') }}" autocomplete="off" required autofocus
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
            placeholder="Seu nome completo">
        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome de Usuário</label>
        <input type="text" name="username" id="username" value="{{ old('username') }}" autocomplete="off" required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
            placeholder="seu_usuario">
        <p class="text-xs text-gray-500 mt-1">Apenas letras, números, traços e underscores.</p>
        @error('username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="off" required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
            placeholder="seu@email.com">
        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Senha</label>
        <input type="password" name="password" id="password" autocomplete="off" required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
            placeholder="Mínimo 8 caracteres">
        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmar Senha</label>
        <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="off" required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
            placeholder="Repita a senha">
    </div>

    <button type="submit" class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition">
        <i class="fas fa-user-plus mr-2"></i> Criar Conta
    </button>

    <p class="text-center text-sm text-gray-500 dark:text-gray-400">
        Já tem uma conta? <a href="{{ route('login') }}" class="text-primary-600 hover:underline font-medium">Entrar</a>
    </p>
</form>
@endsection
