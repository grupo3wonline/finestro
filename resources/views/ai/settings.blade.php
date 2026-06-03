@extends('layouts.app')

@section('title', 'Configurar IA')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('ai.index') }}" class="text-gray-500 hover:text-primary-600">IA</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Configurações</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-cog text-purple-500 mr-2"></i> Configurar Inteligência Artificial
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Insira suas chaves de API para utilizar os recursos de IA. Cada provedor oferece modelos gratuitos.</p>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
        <h4 class="font-medium text-blue-900 dark:text-blue-300 mb-2"><i class="fas fa-info-circle mr-1"></i> Como funciona?</h4>
        <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-1">
            <li>• Suas chaves são armazenadas de forma criptografada e segura</li>
            <li>• Cada usuário utiliza apenas suas próprias credenciais</li>
            <li>• Os modelos utilizados são gratuitos nos respectivos provedores</li>
            <li>• Selecione o provedor preferido para as análises</li>
        </ul>
    </div>

    <!-- Form -->
    <form action="{{ route('ai.credentials.save') }}" method="POST" data-loading="Salvando credenciais..." class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
        @csrf

        <!-- Preferred Provider -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Provedor Preferido *</label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <label class="relative cursor-pointer">
                    <input type="radio" name="preferred_provider" value="gemini" {{ old('preferred_provider', $credentials?->preferred_provider ?? 'gemini') === 'gemini' ? 'checked' : '' }} class="peer sr-only">
                    <div class="p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl text-center peer-checked:border-purple-500 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-900/20 transition">
                        <i class="fas fa-gem text-2xl text-blue-500 mb-2"></i>
                        <p class="font-medium text-gray-900 dark:text-white text-sm">Gemini</p>
                        <p class="text-xs text-gray-500">Google AI</p>
                    </div>
                </label>
                <label class="relative cursor-pointer">
                    <input type="radio" name="preferred_provider" value="groq" {{ old('preferred_provider', $credentials?->preferred_provider) === 'groq' ? 'checked' : '' }} class="peer sr-only">
                    <div class="p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl text-center peer-checked:border-purple-500 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-900/20 transition">
                        <i class="fas fa-bolt text-2xl text-orange-500 mb-2"></i>
                        <p class="font-medium text-gray-900 dark:text-white text-sm">Groq</p>
                        <p class="text-xs text-gray-500">Llama 3.3 70B</p>
                    </div>
                </label>
                <label class="relative cursor-pointer">
                    <input type="radio" name="preferred_provider" value="openrouter" {{ old('preferred_provider', $credentials?->preferred_provider) === 'openrouter' ? 'checked' : '' }} class="peer sr-only">
                    <div class="p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl text-center peer-checked:border-purple-500 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-900/20 transition">
                        <i class="fas fa-route text-2xl text-green-500 mb-2"></i>
                        <p class="font-medium text-gray-900 dark:text-white text-sm">OpenRouter</p>
                        <p class="text-xs text-gray-500">Gemma 3n (free)</p>
                    </div>
                </label>
            </div>
            @error('preferred_provider')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Gemini API Key -->
        <div>
            <label for="gemini_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                <i class="fas fa-gem text-blue-500 mr-1"></i> Chave API Gemini
            </label>
            <input type="password" id="gemini_api_key" name="gemini_api_key" autocomplete="off"
                placeholder="Cole sua chave da API Gemini aqui"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500">
            <p class="text-xs text-gray-500 mt-1">Obtenha em: <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener" class="text-primary-600 hover:underline">aistudio.google.com/apikey</a>
                @if($credentials?->gemini_api_key) <span class="text-green-500 ml-2"><i class="fas fa-check-circle"></i> Configurada</span> @endif
            </p>
            @error('gemini_api_key')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Groq API Key -->
        <div>
            <label for="groq_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                <i class="fas fa-bolt text-orange-500 mr-1"></i> Chave API Groq
            </label>
            <input type="password" id="groq_api_key" name="groq_api_key" autocomplete="off"
                placeholder="Cole sua chave da API Groq aqui"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500">
            <p class="text-xs text-gray-500 mt-1">Obtenha em: <a href="https://console.groq.com/keys" target="_blank" rel="noopener" class="text-primary-600 hover:underline">console.groq.com/keys</a>
                @if($credentials?->groq_api_key) <span class="text-green-500 ml-2"><i class="fas fa-check-circle"></i> Configurada</span> @endif
            </p>
            @error('groq_api_key')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- OpenRouter API Key -->
        <div>
            <label for="openrouter_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                <i class="fas fa-route text-green-500 mr-1"></i> Chave API OpenRouter
            </label>
            <input type="password" id="openrouter_api_key" name="openrouter_api_key" autocomplete="off"
                placeholder="Cole sua chave da API OpenRouter aqui"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500">
            <p class="text-xs text-gray-500 mt-1">Obtenha em: <a href="https://openrouter.ai/keys" target="_blank" rel="noopener" class="text-primary-600 hover:underline">openrouter.ai/keys</a>
                @if($credentials?->openrouter_api_key) <span class="text-green-500 ml-2"><i class="fas fa-check-circle"></i> Configurada</span> @endif
            </p>
            @error('openrouter_api_key')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('ai.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                <i class="fas fa-arrow-left mr-1"></i> Voltar
            </a>
            <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition">
                <i class="fas fa-save mr-2"></i> Salvar Credenciais
            </button>
        </div>
    </form>

    <!-- Security Note -->
    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
        <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-2"><i class="fas fa-shield-alt text-green-500 mr-1"></i> Segurança</h4>
        <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
            <li>• Suas chaves são criptografadas com AES-256 antes de serem armazenadas</li>
            <li>• Nenhum dado financeiro é enviado para servidores externos além do provedor de IA selecionado</li>
            <li>• Você pode remover suas chaves a qualquer momento</li>
            <li>• Em conformidade com a LGPD</li>
        </ul>
    </div>
</div>
@endsection
