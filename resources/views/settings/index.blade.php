@extends('layouts.app')

@section('title', 'Configurações')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-surface-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-surface-400 text-xs"></i></li>
        <li class="text-surface-700 dark:text-surface-300 font-medium">Configurações</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-surface-900 dark:text-white">Configurações</h1>
        <p class="text-surface-600 dark:text-surface-400 text-sm">Gerencie sua conta, segurança e preferências.</p>
    </div>

    <!-- Profile Info -->
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800">
        <div class="p-6 border-b border-surface-100 dark:border-surface-800">
            <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
                <i class="fas fa-user text-primary-500 mr-2"></i> Informações da Conta
            </h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-surface-500 dark:text-surface-400">Nome</label>
                    <p class="text-surface-900 dark:text-white font-medium">{{ $user->name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-surface-500 dark:text-surface-400">Usuário</label>
                    <p class="text-surface-900 dark:text-white font-medium">{{ $user->username }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-surface-500 dark:text-surface-400">E-mail</label>
                    <p class="text-surface-900 dark:text-white font-medium">{{ $user->email }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-surface-500 dark:text-surface-400">Membro desde</label>
                    <p class="text-surface-900 dark:text-white font-medium">{{ $user->created_at->format('d/m/Y') }}</p>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center text-sm text-primary-600 hover:text-primary-700 font-medium">
                <i class="fas fa-edit mr-1"></i> Editar Perfil
            </a>
        </div>
    </div>

    <!-- Change Password -->
    <div id="alterar-senha" class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800">
        <div class="p-6 border-b border-surface-100 dark:border-surface-800">
            <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
                <i class="fas fa-lock text-yellow-500 mr-2"></i> Alterar Senha
            </h2>
            <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">
                Por segurança, a senha precisa ser trocada a cada {{ config('security.password_max_age_days') }} dias.
                A sua é válida até <strong>{{ $user->passwordExpiresAt()->format('d/m/Y') }}</strong>
                ({{ $user->passwordDaysRemaining() === 1 ? '1 dia restante' : $user->passwordDaysRemaining().' dias restantes' }}).
            </p>
        </div>
        <form action="{{ route('settings.password') }}" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Senha Atual</label>
                <input type="password" name="current_password" id="current_password" required autocomplete="off"
                    class="w-full px-4 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
                @error('current_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Nova Senha</label>
                <input type="password" name="password" id="password" required autocomplete="off"
                    class="w-full px-4 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Confirmar Nova Senha</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="off"
                    class="w-full px-4 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
            </div>

            <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-save mr-1"></i> Atualizar Senha
            </button>
        </form>
    </div>

    <!-- Account Activity -->
    <x-ui.section-card title="Atividade da conta" icon="fa-clock-rotate-left">
        <p class="text-sm text-surface-600 dark:text-surface-400 mb-4">
            Veja quando você entrou na conta, tentativas de acesso sem sucesso e as alterações feitas em carteiras, transações, metas, orçamentos, categorias e assinatura.
            Notou algo que não reconhece? Altere sua senha imediatamente.
        </p>
        <x-ui.button :href="route('settings.activity')" variant="secondary" size="sm" icon="fa-clock-rotate-left" title="Ver o histórico de atividade da sua conta">
            Ver atividade da conta
        </x-ui.button>
    </x-ui.section-card>

    <!-- API Tokens -->
    <x-ui.section-card title="Tokens de API" icon="fa-key">
        <p class="text-sm text-surface-600 dark:text-surface-400 mb-4">
            Conecte planilhas, automações e outros sistemas ao Finestro pela API REST.
            Cada token tem as permissões que você escolher e pode ser revogado a qualquer momento.
            @unless($user->hasFeature('api_access'))
                Disponível no plano Profissional.
            @endunless
        </p>
        <x-ui.button :href="route('settings.tokens.index')" variant="secondary" size="sm" icon="fa-key" title="Criar e gerenciar seus tokens de acesso à API">
            Gerenciar tokens de API
        </x-ui.button>
    </x-ui.section-card>

    <!-- Privacy (data export, consent, account deletion) -->
    <x-ui.section-card title="Privacidade e seus dados" icon="fa-user-shield">
        <p class="text-sm text-surface-600 dark:text-surface-400 mb-4">
            Baixe uma cópia de todos os seus dados, revise o histórico de consentimento, ajuste cookies de análise e marketing ou exclua sua conta.
            São os seus direitos pela LGPD, GDPR, CCPA e POPIA.
        </p>
        <x-ui.button :href="route('settings.privacy')" variant="secondary" size="sm" icon="fa-user-shield" title="Exportar dados, consentimentos e exclusão da conta">
            Abrir Privacidade
        </x-ui.button>
    </x-ui.section-card>
</div>
@endsection
