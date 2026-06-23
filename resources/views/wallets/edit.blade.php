@extends('layouts.app')

@section('title', 'Editar Carteira')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('wallets.index') }}" class="text-gray-500 hover:text-primary-600">Carteiras</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Editar</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-edit text-primary-500 mr-2"></i> Editar Carteira
            </h1>
        </div>

        <form action="{{ route('wallets.update', $wallet) }}" method="POST" class="p-6 space-y-6">
            @csrf @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $wallet->name) }}" autocomplete="off" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo <span class="text-red-500">*</span></label>
                <select name="type" id="type" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    <option value="checking" {{ $wallet->type == 'checking' ? 'selected' : '' }}>Conta Corrente</option>
                    <option value="savings" {{ $wallet->type == 'savings' ? 'selected' : '' }}>Poupança</option>
                    <option value="credit" {{ $wallet->type == 'credit' ? 'selected' : '' }}>Cartão de Crédito</option>
                    <option value="investment" {{ $wallet->type == 'investment' ? 'selected' : '' }}>Investimento</option>
                    <option value="cash" {{ $wallet->type == 'cash' ? 'selected' : '' }}>Dinheiro</option>
                </select>
                @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Categoria <span class="text-red-500">*</span>
                    <span class="text-xs text-gray-400 ml-1" title="Separe suas finanças pessoais das empresariais"><i class="fas fa-question-circle"></i></span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative flex items-center justify-center gap-2 p-3 border-2 rounded-lg cursor-pointer transition
                        {{ old('ownership', $wallet->ownership) == 'personal' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-primary-300' }}">
                        <input type="radio" name="ownership" value="personal" {{ old('ownership', $wallet->ownership) == 'personal' ? 'checked' : '' }} class="sr-only peer">
                        <i class="fas fa-user text-primary-600"></i>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pessoal</span>
                        <div class="absolute inset-0 rounded-lg peer-checked:ring-2 peer-checked:ring-primary-500 pointer-events-none"></div>
                    </label>
                    <label class="relative flex items-center justify-center gap-2 p-3 border-2 rounded-lg cursor-pointer transition
                        {{ old('ownership', $wallet->ownership) == 'business' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-primary-300' }}">
                        <input type="radio" name="ownership" value="business" {{ old('ownership', $wallet->ownership) == 'business' ? 'checked' : '' }} class="sr-only peer">
                        <i class="fas fa-building text-purple-600"></i>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Empresarial</span>
                        <div class="absolute inset-0 rounded-lg peer-checked:ring-2 peer-checked:ring-primary-500 pointer-events-none"></div>
                    </label>
                </div>
                @error('ownership') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cor</label>
                    <input type="color" name="color" id="color" value="{{ old('color', $wallet->color) }}"
                        class="w-full h-10 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer">
                </div>
                <div>
                    <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ícone</label>
                    <select name="icon" id="icon"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
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
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição</label>
                <textarea name="description" id="description" rows="3" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">{{ old('description', $wallet->description) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('wallets.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                    <i class="fas fa-save mr-1"></i> Atualizar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
