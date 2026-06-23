@extends('layouts.guest')

@section('title', 'Aceitar Termos de Uso')

@section('content')
<div class="text-center mb-6">
    <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-file-contract text-2xl text-primary-600"></i>
    </div>
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Termos de Uso</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Para continuar utilizando o Finestro, é necessário aceitar nossos Termos de Uso e Política de Privacidade.</p>
</div>

<!-- Terms Summary -->
<div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6 max-h-64 overflow-y-auto text-sm text-gray-600 dark:text-gray-400 space-y-3">
    <h3 class="font-semibold text-gray-900 dark:text-white">Resumo dos Termos</h3>
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

    <div class="pt-3 border-t border-gray-200 dark:border-gray-600">
        <p>Leia os documentos completos:</p>
        <div class="flex gap-4 mt-1">
            <a href="{{ route('terms') }}" target="_blank" class="text-primary-600 hover:underline font-medium text-xs">
                <i class="fas fa-external-link-alt mr-1"></i> Termos de Uso
            </a>
            <a href="{{ route('privacy') }}" target="_blank" class="text-primary-600 hover:underline font-medium text-xs">
                <i class="fas fa-external-link-alt mr-1"></i> Política de Privacidade
            </a>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('terms.store') }}" class="space-y-4">
    @csrf

    <div class="flex items-start gap-2">
        <input type="checkbox" name="terms" id="terms" value="1" required
            class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
        <label for="terms" class="text-sm text-gray-600 dark:text-gray-400">
            Li e aceito os <strong>Termos de Uso</strong> e a <strong>Política de Privacidade</strong> do Finestro. <span class="text-red-500">*</span>
        </label>
    </div>
    @error('terms') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

    <button type="submit" class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition">
        <i class="fas fa-check mr-2"></i> Aceitar e Continuar
    </button>
</form>

<form method="POST" action="{{ route('logout') }}" class="text-center mt-4">
    @csrf
    <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition">
        <i class="fas fa-sign-out-alt mr-1"></i> Sair da conta
    </button>
</form>
@endsection
