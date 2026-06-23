@extends('layouts.app')

@section('title', 'Nova Carteira')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('wallets.index') }}" class="text-gray-500 hover:text-primary-600">Carteiras</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Nova Carteira</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-wallet text-primary-500 mr-2"></i> Nova Carteira
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Crie uma nova carteira para organizar suas finanças. Exemplo: Conta Corrente, Poupança, Cartão de Crédito.</p>
        </div>

        <form action="{{ route('wallets.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Nome da Carteira <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" autocomplete="off" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                    placeholder="Ex: Conta Corrente Nubank">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Tipo <span class="text-red-500">*</span>
                </label>
                <select name="type" id="type" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    <option value="">Selecione o tipo</option>
                    <option value="checking" {{ old('type') == 'checking' ? 'selected' : '' }}>Conta Corrente</option>
                    <option value="savings" {{ old('type') == 'savings' ? 'selected' : '' }}>Poupança</option>
                    <option value="credit" {{ old('type') == 'credit' ? 'selected' : '' }}>Cartão de Crédito</option>
                    <option value="investment" {{ old('type') == 'investment' ? 'selected' : '' }}>Investimento</option>
                    <option value="cash" {{ old('type') == 'cash' ? 'selected' : '' }}>Dinheiro</option>
                </select>
                @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="ownership" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Categoria <span class="text-red-500">*</span>
                    <span class="text-xs text-gray-400 ml-1" title="Separe suas finanças pessoais das empresariais"><i class="fas fa-question-circle"></i></span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative flex items-center justify-center gap-2 p-3 border-2 rounded-lg cursor-pointer transition
                        {{ old('ownership', 'personal') == 'personal' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-primary-300' }}">
                        <input type="radio" name="ownership" value="personal" {{ old('ownership', 'personal') == 'personal' ? 'checked' : '' }} class="sr-only peer">
                        <i class="fas fa-user text-primary-600"></i>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pessoal</span>
                        <div class="absolute inset-0 rounded-lg peer-checked:ring-2 peer-checked:ring-primary-500 pointer-events-none"></div>
                    </label>
                    <label class="relative flex items-center justify-center gap-2 p-3 border-2 rounded-lg cursor-pointer transition
                        {{ old('ownership') == 'business' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-primary-300' }}">
                        <input type="radio" name="ownership" value="business" {{ old('ownership') == 'business' ? 'checked' : '' }} class="sr-only peer">
                        <i class="fas fa-building text-purple-600"></i>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Empresarial</span>
                        <div class="absolute inset-0 rounded-lg peer-checked:ring-2 peer-checked:ring-primary-500 pointer-events-none"></div>
                    </label>
                </div>
                @error('ownership') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="balance" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Saldo Inicial
                </label>
                <input type="number" name="balance" id="balance" value="{{ old('balance', '0.00') }}" step="0.01" min="0" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                    placeholder="0,00">
                @error('balance') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Business Fields (shown when ownership = business) -->
            <div x-data="{ showBusiness: {{ old('ownership') === 'business' ? 'true' : 'false' }} }" x-on:change="showBusiness = document.querySelector('input[name=ownership]:checked')?.value === 'business'">
                <div x-show="showBusiness" x-cloak class="space-y-4 p-4 bg-purple-50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-800 rounded-lg">
                    <h4 class="text-sm font-semibold text-purple-700 dark:text-purple-400"><i class="fas fa-building mr-1"></i> Dados Empresariais</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Razão Social</label>
                            <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" autocomplete="off"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                                placeholder="Nome da empresa">
                        </div>
                        <div>
                            <label for="company_document" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CNPJ</label>
                            <input type="text" name="company_document" id="company_document" value="{{ old('company_document') }}" autocomplete="off"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                                placeholder="00.000.000/0000-00">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_shared" id="is_shared" value="1" {{ old('is_shared') ? 'checked' : '' }}
                            class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                        <label for="is_shared" class="text-sm text-gray-600 dark:text-gray-400">
                            <i class="fas fa-users mr-1"></i> Permitir compartilhar esta carteira com outros usuários
                        </label>
                    </div>
                </div>
            </div>

            <!-- Credit Card Fields (shown when type = credit) -->
            <div x-data="{ showCredit: {{ old('type') === 'credit' ? 'true' : 'false' }} }" x-on:change="showCredit = document.getElementById('type')?.value === 'credit'">
                <div x-show="showCredit" x-cloak class="space-y-4 p-4 bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400"><i class="fas fa-credit-card mr-1"></i> Dados do Cartão de Crédito</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="credit_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Limite</label>
                            <input type="number" name="credit_limit" id="credit_limit" value="{{ old('credit_limit') }}" step="0.01" min="0" autocomplete="off"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                                placeholder="5000.00">
                        </div>
                        <div>
                            <label for="closing_day" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dia Fechamento</label>
                            <input type="number" name="closing_day" id="closing_day" value="{{ old('closing_day') }}" min="1" max="31" autocomplete="off"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                                placeholder="25">
                        </div>
                        <div>
                            <label for="due_day" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dia Vencimento</label>
                            <input type="number" name="due_day" id="due_day" value="{{ old('due_day') }}" min="1" max="31" autocomplete="off"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                                placeholder="10">
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cor</label>
                    <input type="color" name="color" id="color" value="{{ old('color', '#3B82F6') }}"
                        class="w-full h-10 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer">
                </div>
                <div>
                    <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ícone</label>
                    <select name="icon" id="icon"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                        <option value="fa-wallet">💳 Carteira</option>
                        <option value="fa-university">🏦 Banco</option>
                        <option value="fa-credit-card">💳 Cartão</option>
                        <option value="fa-piggy-bank">🐷 Poupança</option>
                        <option value="fa-chart-line">📈 Investimento</option>
                        <option value="fa-money-bill">💵 Dinheiro</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição</label>
                <textarea name="description" id="description" rows="3" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                    placeholder="Descrição opcional da carteira...">{{ old('description') }}</textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('wallets.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                    <i class="fas fa-save mr-1"></i> Salvar Carteira
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
