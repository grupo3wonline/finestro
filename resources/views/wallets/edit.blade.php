@extends('layouts.app')

@section('title', 'Editar Carteira')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-surface-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-surface-400 text-xs"></i></li>
        <li><a href="{{ route('wallets.index') }}" class="text-surface-500 hover:text-primary-600">Carteiras</a></li>
        <li><i class="fas fa-chevron-right text-surface-400 text-xs"></i></li>
        <li class="text-surface-700 dark:text-surface-300 font-medium">Editar</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="section-card">
        <div class="p-6 border-b border-surface-100 dark:border-surface-800">
            <h1 class="text-xl font-semibold text-surface-900 dark:text-white">
                <i class="fas fa-edit text-primary-500 mr-2"></i> Editar Carteira
            </h1>
        </div>

        <form action="{{ route('wallets.update', $wallet) }}" method="POST" class="p-6 space-y-6">
            @csrf @method('PUT')

            <div>
                <label for="name" class="input-label">Nome <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $wallet->name) }}" autocomplete="off" required class="input">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="type" class="input-label">Tipo <span class="text-red-500">*</span></label>
                <select name="type" id="type" required class="input">
                    <option value="checking" {{ $wallet->type == 'checking' ? 'selected' : '' }}>Conta Corrente</option>
                    <option value="savings" {{ $wallet->type == 'savings' ? 'selected' : '' }}>Poupança</option>
                    <option value="credit" {{ $wallet->type == 'credit' ? 'selected' : '' }}>Cartão de Crédito</option>
                    <option value="investment" {{ $wallet->type == 'investment' ? 'selected' : '' }}>Investimento</option>
                    <option value="cash" {{ $wallet->type == 'cash' ? 'selected' : '' }}>Dinheiro</option>
                </select>
                @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="input-label">
                    Categoria <span class="text-red-500">*</span>
                    <span class="text-xs text-surface-400 ml-1" title="Separe suas finanças pessoais das empresariais"><i class="fas fa-question-circle"></i></span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative flex items-center justify-center gap-2 p-3 border-2 rounded-xl cursor-pointer transition
                        {{ old('ownership', $wallet->ownership) == 'personal' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-surface-200 dark:border-surface-700 hover:border-primary-300' }}">
                        <input type="radio" name="ownership" value="personal" {{ old('ownership', $wallet->ownership) == 'personal' ? 'checked' : '' }} class="sr-only peer">
                        <i class="fas fa-user text-primary-600"></i>
                        <span class="text-sm font-medium text-surface-700 dark:text-surface-300">Pessoal</span>
                        <div class="absolute inset-0 rounded-xl peer-checked:ring-2 peer-checked:ring-primary-500 pointer-events-none"></div>
                    </label>
                    <label class="relative flex items-center justify-center gap-2 p-3 border-2 rounded-xl cursor-pointer transition
                        {{ old('ownership', $wallet->ownership) == 'business' ? 'border-accent-500 bg-accent-50 dark:bg-accent-900/20' : 'border-surface-200 dark:border-surface-700 hover:border-accent-300' }}">
                        <input type="radio" name="ownership" value="business" {{ old('ownership', $wallet->ownership) == 'business' ? 'checked' : '' }} class="sr-only peer">
                        <i class="fas fa-building text-accent-600"></i>
                        <span class="text-sm font-medium text-surface-700 dark:text-surface-300">Empresarial</span>
                        <div class="absolute inset-0 rounded-xl peer-checked:ring-2 peer-checked:ring-accent-500 pointer-events-none"></div>
                    </label>
                </div>
                @error('ownership') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Business Fields (shown when ownership = business) -->
            <div x-data="{ showBusiness: {{ old('ownership', $wallet->ownership) === 'business' ? 'true' : 'false' }} }" x-on:change.window="showBusiness = document.querySelector('input[name=ownership]:checked')?.value === 'business'">
                <div x-show="showBusiness" x-cloak class="space-y-4 p-4 bg-accent-50 dark:bg-accent-900/10 border border-accent-200 dark:border-accent-900/50 rounded-xl">
                    <h4 class="text-sm font-semibold text-accent-800 dark:text-accent-400"><i class="fas fa-building mr-1"></i> Dados Empresariais</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="company_name" class="input-label">Razão Social</label>
                            <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $wallet->company_name) }}" autocomplete="off"
                                class="input" placeholder="Nome da empresa">
                            @error('company_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="company_document" class="input-label">CNPJ</label>
                            <input type="text" name="company_document" id="company_document" value="{{ old('company_document', $wallet->company_document) }}" autocomplete="off"
                                class="input" placeholder="00.000.000/0000-00">
                            @error('company_document') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Credit Card Fields (shown when type = credit) -->
            <div x-data="{ showCredit: {{ old('type', $wallet->type) === 'credit' ? 'true' : 'false' }} }" x-on:change.window="showCredit = document.getElementById('type')?.value === 'credit'">
                <div x-show="showCredit" x-cloak class="space-y-4 p-4 bg-primary-50 dark:bg-primary-900/10 border border-primary-200 dark:border-primary-900/50 rounded-xl">
                    <h4 class="text-sm font-semibold text-primary-700 dark:text-primary-400">
                        <i class="fas fa-credit-card mr-1"></i> Dados do Cartão de Crédito
                        <span class="text-xs text-surface-400 ml-1" title="O dia de fechamento é usado para montar a fatura do cartão"><i class="fas fa-question-circle"></i></span>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="credit_limit" class="input-label">Limite</label>
                            <input type="number" name="credit_limit" id="credit_limit" value="{{ old('credit_limit', $wallet->credit_limit) }}" step="0.01" min="0" autocomplete="off"
                                class="input" placeholder="5000.00">
                            @error('credit_limit') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="closing_day" class="input-label">Dia Fechamento</label>
                            <input type="number" name="closing_day" id="closing_day" value="{{ old('closing_day', $wallet->closing_day) }}" min="1" max="31" autocomplete="off"
                                class="input" placeholder="25">
                            @error('closing_day') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="due_day" class="input-label">Dia Vencimento</label>
                            <input type="number" name="due_day" id="due_day" value="{{ old('due_day', $wallet->due_day) }}" min="1" max="31" autocomplete="off"
                                class="input" placeholder="10">
                            @error('due_day') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="color" class="input-label">Cor</label>
                    <input type="color" name="color" id="color" value="{{ old('color', $wallet->color) }}"
                        class="w-full h-10 border border-surface-200 dark:border-surface-600 rounded-xl cursor-pointer bg-white dark:bg-surface-800">
                </div>
                <div>
                    <label for="icon" class="input-label">Ícone</label>
                    <select name="icon" id="icon" class="input">
                        <option value="fa-wallet" {{ $wallet->icon == 'fa-wallet' ? 'selected' : '' }}>💳 Carteira</option>
                        <option value="fa-university" {{ $wallet->icon == 'fa-university' ? 'selected' : '' }}>🏦 Banco</option>
                        <option value="fa-credit-card" {{ $wallet->icon == 'fa-credit-card' ? 'selected' : '' }}>💳 Cartão</option>
                        <option value="fa-piggy-bank" {{ $wallet->icon == 'fa-piggy-bank' ? 'selected' : '' }}>🐷 Poupança</option>
                        <option value="fa-chart-line" {{ $wallet->icon == 'fa-chart-line' ? 'selected' : '' }}>📈 Investimento</option>
                        <option value="fa-money-bill" {{ $wallet->icon == 'fa-money-bill' ? 'selected' : '' }}>💵 Dinheiro</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="description" class="input-label">Descrição</label>
                <textarea name="description" id="description" rows="3" autocomplete="off" class="input">{{ old('description', $wallet->description) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-100 dark:border-surface-800">
                <a href="{{ route('wallets.index') }}" class="btn-ghost">Cancelar</a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-1"></i> Atualizar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
