@extends('layouts.guest')

@section('title', 'Redefinir Senha')

@section('content')
<h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-6">Redefinir Senha</h2>
<p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">Crie uma nova senha para sua conta.</p>

<form method="POST" action="{{ route('password.store') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail</label>
        <input type="email" name="email" id="email" value="{{ old('email', $request->email) }}" autocomplete="off" required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nova Senha</label>
        <input type="password" name="password" id="password" autocomplete="off" required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
            placeholder="Mínimo 8 caracteres">
        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmar Nova Senha</label>
        <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="off" required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
            placeholder="Repita a nova senha">
    </div>

    <button type="submit" class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition">
        <i class="fas fa-key mr-2"></i> Redefinir Senha
    </button>
</form>
@endsection
