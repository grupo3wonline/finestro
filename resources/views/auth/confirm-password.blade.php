@extends('layouts.guest')

@section('title', 'Confirmar Senha')
@section('meta_description', 'Confirme sua senha para continuar em uma área protegida do Finestro.')

@section('content')
<div class="text-center mb-6">
    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-accent-50 dark:bg-accent-900/30 flex items-center justify-center">
        <i class="fas fa-shield-halved text-2xl text-accent-600 dark:text-accent-400"></i>
    </div>
    <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Confirmar senha</h2>
    <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">
        Esta é uma área segura da aplicação. Confirme sua senha antes de continuar.
    </p>
</div>

<form method="POST" action="{{ route('password.confirm') }}" class="space-y-4" data-loading="Confirmando...">
    @csrf

    <x-ui.field label="Senha" for="password" error="password">
        <x-ui.input type="password" name="password" icon="fa-lock" error="password" required autofocus />
    </x-ui.field>

    <x-ui.button type="submit" icon="fa-lock" block class="py-3">Confirmar</x-ui.button>
</form>
@endsection
