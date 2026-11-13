@extends('layouts.app')

@section('title', 'Nova Carteira')

@section('breadcrumb')
<x-ui.breadcrumb :items="[
    ['label' => 'Carteiras', 'url' => route('wallets.index')],
    ['label' => 'Nova Carteira'],
]" />
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="section-card">
        <div class="p-6 border-b border-surface-100 dark:border-surface-800" x-data="{ helpOpen: false }">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-surface-900 dark:text-white">
                        <i class="fas fa-wallet text-primary-500 mr-2"></i> Nova Carteira
                    </h1>
                    <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">Crie uma nova carteira para organizar suas finanças. Exemplo: Conta Corrente, Poupança, Cartão de Crédito.</p>
                </div>
                <button type="button" @click="helpOpen = !helpOpen"
                    class="p-1.5 rounded-lg text-surface-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition shrink-0 mt-0.5"
                    aria-label="Ajuda">
                    <i class="fas fa-question-circle"></i>
                </button>
            </div>
            <div x-show="helpOpen" x-cloak x-transition
                class="mt-4 p-4 bg-primary-50 dark:bg-primary-900/20 border border-primary-100 dark:border-primary-800/40 rounded-xl text-sm text-primary-800 dark:text-primary-300">
                <p class="font-medium mb-2"><i class="fas fa-info-circle mr-1"></i> Sobre as Carteiras</p>
                <p class="mb-2">Uma carteira representa uma conta bancária, cartão de crédito, dinheiro em espécie ou qualquer fonte de recursos financeiros.</p>
                <ul class="space-y-1 text-xs list-disc list-inside text-primary-700 dark:text-primary-400">
                    <li>Dica: crie uma carteira para cada conta real que você usa.</li>
                    <li>Para cartão de crédito, o saldo pode ser negativo (valor da fatura).</li>
                    <li>Carteiras empresariais podem ser compartilhadas com outros usuários.</li>
                </ul>
            </div>
        </div>

        <form autocomplete="off" action="{{ route('wallets.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <div>
                <label for="name" class="input-label">Nome da Carteira <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" autocomplete="off" required
                    class="input" placeholder="Ex: Conta Corrente Nubank">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="input-hint">Ex: Nubank, Itaú, Cartão Visa</p>
            </div>

            <div>
                <label for="type" class="input-label">Tipo <span class="text-red-500">*</span></label>
                <x-ui.tooltip text="Conta corrente, poupança, dinheiro em espécie ou cartão de crédito" position="top">
                    <select autocomplete="off" name="type" id="type" required class="input">
                        <option value="">Selecione o tipo</option>
                        <option value="checking" {{ old('type') == 'checking' ? 'selected' : '' }}>Conta Corrente</option>
                        <option value="savings" {{ old('type') == 'savings' ? 'selected' : '' }}>Poupança</option>
                        <option value="credit" {{ old('type') == 'credit' ? 'selected' : '' }}>Cartão de Crédito</option>
                        <option value="investment" {{ old('type') == 'investment' ? 'selected' : '' }}>Investimento</option>
                        <option value="cash" {{ old('type') == 'cash' ? 'selected' : '' }}>Dinheiro</option>
                    </select>
                </x-ui.tooltip>
                @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="input-hint">Conta corrente, poupança, dinheiro em espécie ou cartão de crédito</p>
            </div>

            <div>
                <label for="ownership" class="input-label">
                    Categoria <span class="text-red-500">*</span>
                    <span class="text-xs text-surface-400 ml-1" title="Separe suas finanças pessoais das empresariais"><i class="fas fa-question-circle"></i></span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative flex items-center justify-center gap-2 p-3 border-2 rounded-xl cursor-pointer transition
                        {{ old('ownership', 'personal') == 'personal' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-surface-200 dark:border-surface-700 hover:border-primary-300' }}">
                        <input type="radio" name="ownership" value="personal" {{ old('ownership', 'personal') == 'personal' ? 'checked' : '' }} class="sr-only peer">
                        <i class="fas fa-user text-primary-600"></i>
                        <span class="text-sm font-medium text-surface-700 dark:text-surface-300">Pessoal</span>
                        <div class="absolute inset-0 rounded-xl peer-checked:ring-2 peer-checked:ring-primary-500 pointer-events-none"></div>
                    </label>
                    <label class="relative flex items-center justify-center gap-2 p-3 border-2 rounded-xl cursor-pointer transition
                        {{ old('ownership') == 'business' ? 'border-accent-500 bg-accent-50 dark:bg-accent-900/20' : 'border-surface-200 dark:border-surface-700 hover:border-accent-300' }}">
                        <input type="radio" name="ownership" value="business" {{ old('ownership') == 'business' ? 'checked' : '' }} class="sr-only peer">
                        <i class="fas fa-building text-accent-600"></i>
                        <span class="text-sm font-medium text-surface-700 dark:text-surface-300">Empresarial</span>
                        <div class="absolute inset-0 rounded-xl peer-checked:ring-2 peer-checked:ring-accent-500 pointer-events-none"></div>
                    </label>
                </div>
                @error('ownership') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="balance" class="input-label">Saldo Inicial</label>
                <x-ui.tooltip text="Saldo atual na data de hoje. Pode ser negativo para cartão de crédito." position="top">
                    <input type="number" name="balance" id="balance" value="{{ old('balance', '0.00') }}" step="0.01" autocomplete="off"
                        class="input" placeholder="0,00">
                </x-ui.tooltip>
                @error('balance') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="input-hint">Saldo atual na data de hoje. Pode ser negativo para cartão de crédito.</p>
            </div>

            <!-- Business Fields (shown when ownership = business) -->
            <div x-data="{ showBusiness: {{ old('ownership') === 'business' ? 'true' : 'false' }} }" x-on:change="showBusiness = document.querySelector('input[name=ownership]:checked')?.value === 'business'">
                <div x-show="showBusiness" x-cloak class="space-y-4 p-4 bg-accent-50 dark:bg-accent-900/10 border border-accent-200 dark:border-accent-900/50 rounded-xl">
                    <h4 class="text-sm font-semibold text-accent-800 dark:text-accent-400"><i class="fas fa-building mr-1"></i> Dados Empresariais</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="company_name" class="input-label">Razão Social</label>
                            <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" autocomplete="off"
                                class="input" placeholder="Nome da empresa">
                        </div>
                        <div>
                            <label for="company_document" class="input-label">CNPJ</label>
                            <input type="text" name="company_document" id="company_document" value="{{ old('company_document') }}" autocomplete="off"
                                class="input" placeholder="00.000.000/0000-00">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_shared" id="is_shared" value="1" {{ old('is_shared') ? 'checked' : '' }}
                            class="rounded border-surface-300 dark:border-surface-600 text-primary-600 focus:ring-primary-500">
                        <label for="is_shared" class="text-sm text-surface-600 dark:text-surface-400">
                            <i class="fas fa-users mr-1"></i> Permitir compartilhar esta carteira com outros usuários
                        </label>
                    </div>
                </div>
            </div>

            <!-- Credit Card Fields (shown when type = credit) -->
            <div x-data="{ showCredit: {{ old('type') === 'credit' ? 'true' : 'false' }} }" x-on:change="showCredit = document.getElementById('type')?.value === 'credit'">
                <div x-show="showCredit" x-cloak class="space-y-4 p-4 bg-primary-50 dark:bg-primary-900/10 border border-primary-200 dark:border-primary-900/50 rounded-xl">
                    <h4 class="text-sm font-semibold text-primary-700 dark:text-primary-400"><i class="fas fa-credit-card mr-1"></i> Dados do Cartão de Crédito</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="credit_limit" class="input-label">Limite</label>
                            <input type="number" name="credit_limit" id="credit_limit" value="{{ old('credit_limit') }}" step="0.01" min="0" autocomplete="off"
                                class="input" placeholder="5000.00">
                        </div>
                        <div>
                            <label for="closing_day" class="input-label">Dia Fechamento</label>
                            <x-ui.tooltip text="Dia do mês em que a fatura fecha (ex: 25)" position="top">
                                <input type="number" name="closing_day" id="closing_day" value="{{ old('closing_day') }}" min="1" max="31" autocomplete="off"
                                    class="input" placeholder="25">
                            </x-ui.tooltip>
                        </div>
                        <div>
                            <label for="due_day" class="input-label">Dia Vencimento</label>
                            <x-ui.tooltip text="Dia do mês em que a fatura vence (ex: 10)" position="top">
                                <input type="number" name="due_day" id="due_day" value="{{ old('due_day') }}" min="1" max="31" autocomplete="off"
                                    class="input" placeholder="10">
                            </x-ui.tooltip>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="color" class="input-label">Cor</label>
                    <input type="color" name="color" id="color" value="{{ old('color', '#039855') }}"
                        class="w-full h-10 border border-surface-200 dark:border-surface-600 rounded-xl cursor-pointer bg-white dark:bg-surface-800">
                </div>
                <div>
                    <label for="icon" class="input-label">Ícone</label>
                    <select autocomplete="off" name="icon" id="icon" class="input">
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
                <label for="description" class="input-label">Descrição</label>
                <textarea name="description" id="description" rows="3" autocomplete="off"
                    class="input" placeholder="Descrição opcional da carteira...">{{ old('description') }}</textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-100 dark:border-surface-800">
                <a href="{{ route('wallets.index') }}" class="btn-ghost">Cancelar</a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-1"></i> Salvar Carteira
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
