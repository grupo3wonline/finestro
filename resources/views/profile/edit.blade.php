@extends('layouts.app')

@section('title', 'Meu Perfil')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Meu Perfil</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Meu Perfil</h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Gerencie suas informações pessoais e segurança da conta.</p>
    </div>

    <!-- Update Profile Information -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
            <i class="fas fa-user text-primary-500 mr-2"></i> Informações Pessoais
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Atualize seu nome e endereço de e-mail.</p>

        @include('profile.partials.update-profile-information-form')
    </div>

    <!-- Update Password -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
            <i class="fas fa-lock text-primary-500 mr-2"></i> Alterar Senha
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Use uma senha forte com pelo menos 8 caracteres.</p>

        @include('profile.partials.update-password-form')
    </div>

    <!-- Delete Account -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-200 dark:border-red-900 p-6">
        <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-1">
            <i class="fas fa-exclamation-triangle mr-2"></i> Excluir Conta
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Após excluir sua conta, todos os dados serão permanentemente removidos.</p>

        @include('profile.partials.delete-user-form')
    </div>
</div>
@endsection
