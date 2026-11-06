@extends('layouts.guest')

@section('title', 'Recuperar Senha')
@section('meta_description', 'Esqueceu a senha do Finestro? Informe seu e-mail para receber o link de redefinição.')

@section('content')
<div class="text-center mb-6">
    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
        <i class="fas fa-key text-2xl text-primary-600 dark:text-primary-400"></i>
    </div>
    <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Recuperar senha</h2>
    <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">Informe seu e-mail e enviaremos um link para redefinir sua senha.</p>
</div>

@if(session('status'))
    <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
@endif

<form autocomplete="off" method="POST" action="{{ route('password.email') }}" class="space-y-4" data-loading="Enviando...">
    @csrf

    <x-ui.field label="E-mail" for="email" error="email">
        <x-ui.input type="email" name="email" icon="fa-envelope" error="email"
            value="{{ old('email') }}" required autofocus placeholder="seu@email.com" />
    </x-ui.field>

    <x-ui.captcha action="forgot_password" />

    <x-ui.button type="submit" icon="fa-paper-plane" block class="py-3">Enviar link de recuperação</x-ui.button>

    <p class="text-center text-sm text-surface-500 dark:text-surface-400">
        <a href="{{ route('login') }}" class="text-primary-600 hover:underline"><i class="fas fa-arrow-left mr-1"></i> Voltar ao login</a>
    </p>
</form>
@endsection
