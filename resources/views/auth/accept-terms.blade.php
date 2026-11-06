@extends('layouts.guest')

@section('title', 'Aceitar Termos de Uso')
@section('meta_description', 'Leia e aceite a versão atual dos Termos de Uso e da Política de Privacidade do Finestro para continuar.')

@section('content')
<div class="text-center mb-6">
    <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-file-contract text-2xl text-primary-600"></i>
    </div>
    <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Termos de Uso</h2>
    <p class="text-sm text-surface-500 dark:text-surface-400 mt-2">Para continuar utilizando o Finestro, é necessário aceitar nossos Termos de Uso e Política de Privacidade.</p>
</div>

@if($previouslyAccepted ?? false)
    <x-ui.alert variant="info" class="mb-4">
        Atualizamos nossos Termos de Uso e Política de Privacidade (versão {{ $policyVersion }}). Revise e aceite para continuar.
    </x-ui.alert>
@endif

<!-- Terms Summary -->
<div class="bg-surface-50 dark:bg-surface-800/60 rounded-lg p-4 mb-6 max-h-64 overflow-y-auto text-sm text-surface-600 dark:text-surface-400 space-y-3">
    <h3 class="font-semibold text-surface-900 dark:text-white">Resumo dos Termos</h3>
    <ul class="space-y-2">
        <li class="flex items-start gap-2">
            <i class="fas fa-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
            <span>Seus dados financeiros são <strong>privados e protegidos</strong> conforme a LGPD.</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
            <span>Cada usuário acessa <strong>somente seus próprios dados</strong>.</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
            <span>O Finestro é uma ferramenta de <strong>organização financeira</strong>, não constitui aconselhamento profissional.</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
            <span>Você pode <strong>solicitar exclusão dos seus dados</strong> a qualquer momento.</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
            <span>Pagamentos são processados com segurança pelo <strong>Stripe</strong>.</span>
        </li>
    </ul>

    <div class="pt-3 border-t border-surface-200 dark:border-surface-700">
        <p>Leia os documentos completos:</p>
        <div class="flex gap-4 mt-1">
            <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:underline font-medium text-xs">
                <i class="fas fa-external-link-alt mr-1"></i> Termos de Uso
            </a>
            <a href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:underline font-medium text-xs">
                <i class="fas fa-external-link-alt mr-1"></i> Política de Privacidade
            </a>
        </div>
    </div>
</div>

<form autocomplete="off" method="POST" action="{{ route('terms.store') }}" class="space-y-4">
    @csrf

    <div class="flex items-start gap-2">
        <input type="checkbox" name="terms" id="terms" value="1" required
            class="mt-0.5 rounded border-surface-200 dark:border-surface-600 text-primary-600 focus:ring-primary-500">
        <label for="terms" class="text-sm text-surface-600 dark:text-surface-400">
            Li e aceito os <strong>Termos de Uso</strong> e a <strong>Política de Privacidade</strong> do Finestro. <span class="text-red-500">*</span>
        </label>
    </div>
    @error('terms') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

    <x-ui.button type="submit" icon="fa-check" block class="py-3">Aceitar e continuar</x-ui.button>
</form>

<p class="text-center mt-4 text-sm text-surface-600 dark:text-surface-400">
    <a href="{{ route('settings.privacy') }}" class="font-medium text-primary-600 dark:text-primary-400 hover:underline"
        title="Abre Configurações > Privacidade, onde você exporta seus dados ou exclui sua conta sem aceitar a nova versão">
        <i class="fas fa-user-shield mr-1" aria-hidden="true"></i> Prefiro exportar ou excluir meus dados
    </a>
</p>

<form autocomplete="off" method="POST" action="{{ route('logout') }}" class="text-center mt-4" data-no-loading>
    @csrf
    <button type="submit" class="text-sm text-surface-500 hover:text-surface-700 dark:hover:text-surface-300 transition">
        <i class="fas fa-sign-out-alt mr-1"></i> Sair da conta
    </button>
</form>
@endsection
