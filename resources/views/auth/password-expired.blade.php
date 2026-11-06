@extends('layouts.guest')

@section('title', 'Troque sua senha')
@section('meta_description', 'Sua senha do Finestro expirou. Defina uma nova senha para continuar.')

@section('content')
<div class="text-center mb-6">
    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-accent-50 dark:bg-accent-900/30 flex items-center justify-center">
        <i class="fas fa-key text-2xl text-accent-600 dark:text-accent-400"></i>
    </div>
    <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Sua senha expirou</h2>
    <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">
        Por segurança, a senha precisa ser trocada a cada {{ config('security.password_max_age_days') }} dias.
        Defina uma nova senha para voltar a usar o Finestro.
    </p>
</div>

<form autocomplete="off" method="POST" action="{{ route('password.expired.update') }}" class="space-y-4" data-loading="Salvando...">
    @csrf
    @method('PUT')

    <x-ui.field label="Senha atual" for="current_password" error="current_password">
        <x-ui.input type="password" name="current_password" icon="fa-lock" error="current_password"
            required autofocus placeholder="Sua senha atual" />
    </x-ui.field>

    <x-ui.field label="Nova senha" for="password" error="password"
        hint="Mínimo de 8 caracteres e diferente da senha atual.">
        <x-ui.input type="password" name="password" icon="fa-key" error="password"
            required placeholder="Crie uma nova senha" />
    </x-ui.field>

    <x-ui.field label="Confirmar nova senha" for="password_confirmation">
        <x-ui.input type="password" name="password_confirmation" icon="fa-key"
            required placeholder="Repita a nova senha" />
    </x-ui.field>

    <x-ui.button type="submit" icon="fa-floppy-disk" block class="py-3">Salvar nova senha</x-ui.button>
</form>

<form autocomplete="off" method="POST" action="{{ route('logout') }}" class="mt-3" data-no-loading>
    @csrf
    <button type="submit" class="w-full py-2 text-sm text-surface-600 dark:text-surface-400 hover:text-surface-900 dark:hover:text-white transition">
        <i class="fas fa-sign-out-alt mr-1"></i> Sair
    </button>
</form>
@endsection
