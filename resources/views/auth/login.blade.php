@extends('layouts.guest')

@section('title', 'Entrar')
@section('meta_description', 'Entre na sua conta Finestro com usuário, e-mail e senha para acessar carteiras, transações, metas e relatórios.')

@section('content')
<div class="text-center mb-6">
    <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Bem-vindo de volta</h2>
    <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">Acesse sua conta para gerenciar suas finanças.</p>
</div>

@if(session('status'))
    <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-4" data-loading="Entrando...">
    @csrf

    @error('login')
        <x-ui.alert variant="danger">
            {{ $message }}
            @if($message === \App\Models\User::UNVERIFIED_MESSAGE)
                <a href="{{ route('verification.resend.create') }}" class="block mt-2 font-semibold underline">
                    <i class="fas fa-paper-plane mr-1"></i> Reenviar e-mail de confirmação
                </a>
            @endif
        </x-ui.alert>
    @enderror

    <x-ui.tip icon="fa-shield-halved">Por segurança, informe usuário, e-mail e senha da mesma conta.</x-ui.tip>

    <x-ui.field
        label="Usuário"
        for="username"
        error="username"
        tooltip="Seu nome de usuário cadastrado.">
        <x-ui.input type="text" name="username" icon="fa-user" error="username"
            value="{{ old('username') }}" required autofocus maxlength="50"
            placeholder="seu_usuario" />
    </x-ui.field>

    <x-ui.field
        label="E-mail"
        for="email"
        error="email"
        tooltip="O e-mail cadastrado na mesma conta do usuário acima.">
        <x-ui.input type="email" name="email" icon="fa-envelope" error="email"
            value="{{ old('email') }}" required maxlength="255"
            placeholder="seu@email.com" />
    </x-ui.field>

    <x-ui.field label="Senha" for="password" error="password">
        <div x-data="{ show: false }" class="input-group">
            <span class="input-group-icon"><i class="fas fa-lock"></i></span>
            <input :type="show ? 'text' : 'password'" name="password" id="password" autocomplete="off" required
                class="input pr-11 {{ $errors->has('password') ? 'has-error' : '' }}" placeholder="Sua senha">
            <button type="button" @click="show = !show" tabindex="-1"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-surface-400 hover:text-surface-600 dark:hover:text-surface-300 transition"
                :aria-label="show ? 'Ocultar senha' : 'Mostrar senha'">
                <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
            </button>
        </div>
    </x-ui.field>

    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-surface-600 dark:text-surface-400 cursor-pointer">
            <input type="checkbox" name="remember" class="rounded border-surface-300 dark:border-surface-600 text-primary-600 focus:ring-primary-500">
            Lembrar-me
        </label>
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="text-sm text-primary-600 hover:underline">Esqueceu a senha?</a>
        @endif
    </div>

    <x-ui.captcha action="login" />

    <x-ui.button type="submit" icon="fa-arrow-right-to-bracket" block class="py-3">Entrar</x-ui.button>

    <p class="text-center text-sm text-surface-500 dark:text-surface-400">
        Não tem conta? <a href="{{ route('register') }}" class="text-primary-600 hover:underline font-medium">Criar conta grátis</a>
    </p>
</form>
@endsection
