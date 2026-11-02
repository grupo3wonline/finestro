@extends('layouts.guest')

@section('title', 'Verificar E-mail')
@section('meta_description', 'Confirme seu e-mail para ativar a sua conta Finestro.')

@section('content')
<div class="text-center mb-6">
    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
        <i class="fas fa-envelope-circle-check text-2xl text-primary-600 dark:text-primary-400"></i>
    </div>
    <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Verifique seu e-mail</h2>
    <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">
        Obrigado por se cadastrar! Antes de começar, confirme seu e-mail pelo link que enviamos. Se não recebeu, reenvie abaixo.
    </p>
</div>

@if (session('status') == 'verification-link-sent')
    <x-ui.alert variant="success" class="mb-4">
        Um novo link de verificação foi enviado para o e-mail informado no cadastro.
    </x-ui.alert>
@endif

<div class="space-y-3">
    <form method="POST" action="{{ route('verification.send') }}" data-loading="Reenviando...">
        @csrf
        <x-ui.button type="submit" icon="fa-paper-plane" block class="py-3">Reenviar e-mail de verificação</x-ui.button>
        <p class="mt-2 text-center text-xs text-surface-500 dark:text-surface-400">Sua conta só é ativada depois da confirmação do e-mail.</p>
    </form>

    <form method="POST" action="{{ route('logout') }}" data-no-loading>
        @csrf
        <button type="submit" class="w-full py-2 text-sm text-surface-600 dark:text-surface-400 hover:text-surface-900 dark:hover:text-white transition">
            <i class="fas fa-sign-out-alt mr-1"></i> Sair
        </button>
    </form>
</div>
@endsection
