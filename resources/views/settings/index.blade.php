@extends('layouts.app')

@section('title', 'Configurações')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Configurações</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Configurações</h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Gerencie sua conta, segurança e preferências.</p>
    </div>

    <!-- Profile Info -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-user text-primary-500 mr-2"></i> Informações da Conta
            </h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Nome</label>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $user->name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Usuário</label>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $user->username }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">E-mail</label>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $user->email }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Membro desde</label>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $user->created_at->format('d/m/Y') }}</p>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center text-sm text-primary-600 hover:text-primary-700 font-medium">
                <i class="fas fa-edit mr-1"></i> Editar Perfil
            </a>
        </div>
    </div>

    <!-- Change Password -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-lock text-yellow-500 mr-2"></i> Alterar Senha
            </h2>
        </div>
        <form action="{{ route('settings.password') }}" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Senha Atual</label>
                <input type="password" name="current_password" id="current_password" required autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
                @error('current_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nova Senha</label>
                <input type="password" name="password" id="password" required autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmar Nova Senha</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
            </div>

            <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-save mr-1"></i> Atualizar Senha
            </button>
        </form>
    </div>

    <!-- Delete Account -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-200 dark:border-red-800">
        <div class="p-6 border-b border-red-200 dark:border-red-800">
            <h2 class="text-lg font-semibold text-red-600">
                <i class="fas fa-exclamation-triangle mr-2"></i> Zona de Perigo
            </h2>
        </div>
        <div class="p-6" x-data="{ showDelete: false }">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Ao excluir sua conta, todos os seus dados serão permanentemente removidos. Esta ação não pode ser desfeita.
            </p>
            <button @click="showDelete = !showDelete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-trash mr-1"></i> Excluir Minha Conta
            </button>

            <div x-show="showDelete" x-cloak class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                <form action="{{ route('settings.account.delete') }}" method="POST" class="space-y-3">
                    @csrf
                    @method('DELETE')
                    <p class="text-sm text-red-700 dark:text-red-400 font-medium">Confirme sua senha para excluir a conta:</p>
                    <input type="password" name="password" required autocomplete="off" placeholder="Sua senha"
                        class="w-full px-4 py-2 border border-red-300 dark:border-red-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 transition">
                    @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    <button type="submit" class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white text-sm font-medium rounded-lg transition"
                        onclick="return confirm('TEM CERTEZA? Esta ação é irreversível.')">
                        Confirmar Exclusão Permanente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
