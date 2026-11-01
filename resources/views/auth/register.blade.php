@extends('layouts.guest')

@section('title', 'Criar Conta')
@section('meta_description', 'Crie sua conta grátis no Finestro e comece a organizar suas finanças pessoais. Plano Básico sem cartão de crédito.')

@section('content')
<div class="text-center mb-6">
    <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Criar conta grátis</h2>
    <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">Preencha os dados abaixo. Leva menos de 2 minutos.</p>
</div>

<form method="POST" action="{{ route('register') }}" class="space-y-4" data-loading="Criando conta...">
    @csrf
    <x-ui.honeypot />

    <x-ui.field label="Nome completo" for="name" error="name">
        <x-ui.input type="text" name="name" icon="fa-id-card" error="name"
            value="{{ old('name') }}" required autofocus placeholder="Seu nome completo" />
    </x-ui.field>

    <x-ui.field label="Nome de usuário" for="username" error="username"
        hint="Apenas letras, números, traços e underscores.">
        <x-ui.input type="text" name="username" icon="fa-at" error="username"
            value="{{ old('username') }}" required placeholder="seu_usuario" />
    </x-ui.field>

    <x-ui.field label="E-mail" for="email" error="email">
        <x-ui.input type="email" name="email" icon="fa-envelope" error="email"
            value="{{ old('email', $invitedEmail ?? '') }}" required placeholder="seu@email.com" />
    </x-ui.field>

    <div x-data="{ pwd: '', get score() {
            let s = 0;
            if (this.pwd.length >= 8) s++;
            if (/[A-Z]/.test(this.pwd)) s++;
            if (/[0-9]/.test(this.pwd)) s++;
            if (/[^A-Za-z0-9]/.test(this.pwd)) s++;
            return s;
        } }">
        <x-ui.field label="Senha" for="password" error="password">
            <div class="input-group">
                <span class="input-group-icon"><i class="fas fa-lock"></i></span>
                <input type="password" name="password" id="password" x-model="pwd" autocomplete="off" required
                    class="input {{ $errors->has('password') ? 'has-error' : '' }}" placeholder="Mínimo 8 caracteres">
            </div>
        </x-ui.field>
        {{-- Medidor de força da senha --}}
        <div class="mt-2" x-show="pwd.length > 0" x-cloak>
            <div class="flex gap-1.5">
                <template x-for="i in 4" :key="i">
                    <div class="h-1.5 flex-1 rounded-full transition-colors duration-300"
                        :class="i <= score ? (score <= 1 ? 'bg-red-500' : score === 2 ? 'bg-accent-500' : score === 3 ? 'bg-secondary-500' : 'bg-primary-500') : 'bg-surface-200 dark:bg-surface-700'"></div>
                </template>
            </div>
            <p class="text-xs mt-1 text-surface-500 dark:text-surface-400"
                x-text="score <= 1 ? 'Senha fraca' : score === 2 ? 'Senha razoável' : score === 3 ? 'Senha boa' : 'Senha forte'"></p>
        </div>
    </div>

    <x-ui.field label="Confirmar senha" for="password_confirmation">
        <x-ui.input type="password" name="password_confirmation" icon="fa-lock" required placeholder="Repita a senha" />
    </x-ui.field>

    <div>
        <label for="terms" class="flex items-start gap-2 cursor-pointer">
            <input type="checkbox" name="terms" id="terms" value="1" {{ old('terms') ? 'checked' : '' }}
                class="mt-0.5 rounded border-surface-300 dark:border-surface-600 text-primary-600 focus:ring-primary-500">
            <span class="text-sm text-surface-600 dark:text-surface-400">
                Li e aceito os <a href="{{ route('terms') }}" target="_blank" class="text-primary-600 hover:underline font-medium">Termos de Uso</a> e a <a href="{{ route('privacy') }}" target="_blank" class="text-primary-600 hover:underline font-medium">Política de Privacidade</a>. <span class="text-red-500">*</span>
            </span>
        </label>
        @error('terms') <p class="input-error mt-1.5"><i class="fas fa-circle-exclamation"></i>{{ $message }}</p> @enderror
    </div>

    <x-ui.button type="submit" icon="fa-user-plus" block class="py-3">Criar conta</x-ui.button>

    <p class="text-center text-sm text-surface-500 dark:text-surface-400">
        Já tem uma conta? <a href="{{ route('login') }}" class="text-primary-600 hover:underline font-medium">Entrar</a>
    </p>
</form>
@endsection
