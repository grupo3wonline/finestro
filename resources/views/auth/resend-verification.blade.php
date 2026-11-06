@extends('layouts.guest')

@section('title', 'Reenviar confirmação')
@section('meta_description', 'Não recebeu o e-mail de confirmação? Solicite um novo link para ativar sua conta Finestro.')

@section('content')
<div class="text-center mb-6">
    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
        <i class="fas fa-envelope-circle-check text-2xl text-primary-600 dark:text-primary-400"></i>
    </div>
    <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Reenviar e-mail de confirmação</h2>
    <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">
        Sua conta só é ativada depois de confirmar o e-mail. Informe o e-mail do cadastro e enviaremos um novo link.
    </p>
</div>

@if(session('status'))
    <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
@endif

<form autocomplete="off" method="POST" action="{{ route('verification.resend.store') }}" class="space-y-4" data-loading="Enviando...">
    @csrf

    <x-ui.field label="E-mail do cadastro" for="email" error="email">
        <x-ui.input type="email" name="email" icon="fa-envelope" error="email"
            value="{{ old('email') }}" required autofocus placeholder="seu@email.com" />
    </x-ui.field>

    <x-ui.captcha action="resend_verification" />

    <x-ui.button type="submit" icon="fa-paper-plane" block class="py-3">Enviar novo link</x-ui.button>

    <x-ui.tip icon="fa-circle-info">Não achou o e-mail? Veja a caixa de spam. Por segurança, enviamos no máximo 3 links por hora para o mesmo endereço.</x-ui.tip>

    <p class="text-center text-sm text-surface-500 dark:text-surface-400">
        <a href="{{ route('login') }}" class="text-primary-600 hover:underline"><i class="fas fa-arrow-left mr-1"></i> Voltar ao login</a>
    </p>
</form>
@endsection
