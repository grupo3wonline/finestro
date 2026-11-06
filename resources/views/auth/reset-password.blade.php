@extends('layouts.guest')

@section('title', 'Redefinir Senha')
@section('meta_description', 'Defina uma nova senha para a sua conta Finestro.')

@section('content')
<div class="text-center mb-6">
    <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Redefinir senha</h2>
    <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">Crie uma nova senha para sua conta.</p>
</div>

<form autocomplete="off" method="POST" action="{{ route('password.store') }}" class="space-y-4" data-loading="Redefinindo...">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <x-ui.field label="E-mail" for="email" error="email">
        <x-ui.input type="email" name="email" icon="fa-envelope" error="email"
            value="{{ old('email', $request->email) }}" required />
    </x-ui.field>

    <x-ui.field label="Nova senha" for="password" error="password">
        <x-ui.input type="password" name="password" icon="fa-lock" error="password"
            required placeholder="Mínimo 8 caracteres" />
    </x-ui.field>

    <x-ui.field label="Confirmar nova senha" for="password_confirmation">
        <x-ui.input type="password" name="password_confirmation" icon="fa-lock"
            required placeholder="Repita a nova senha" />
    </x-ui.field>

    <x-ui.captcha action="reset_password" />

    <x-ui.button type="submit" icon="fa-key" block class="py-3">Redefinir senha</x-ui.button>
</form>
@endsection
