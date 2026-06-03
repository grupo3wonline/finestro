@extends('layouts.guest')

@section('title', 'Confirmar Senha')

@section('content')
<h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-4">Confirmar Senha</h2>
<p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">
    Esta é uma área segura da aplicação. Por favor, confirme sua senha antes de continuar.
</p>

<form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
    @csrf

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Senha</label>
        <input type="password" name="password" id="password" autocomplete="off" required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <button type="submit" class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition">
        <i class="fas fa-lock mr-2"></i> Confirmar
    </button>
</form>
@endsection
