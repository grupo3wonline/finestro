@extends('layouts.app')

@section('title', 'Nova Transação')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('transactions.index') }}" class="text-gray-500 hover:text-primary-600">Transações</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Nova Transação</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-exchange-alt text-primary-500 mr-2"></i> Nova Transação
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Registre uma receita ou despesa. Selecione a carteira e preencha os detalhes.</p>
        </div>

        <form action="{{ route('transactions.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo <span class="text-red-500">*</span></label>
                    <select name="type" id="type" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
                        <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Despesa</option>
                        <option value="income" {{ old('type') == 'income' ? 'selected' : '' }}>Receita</option>
                    </select>
                    @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="wallet_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Carteira <span class="text-red-500">*</span></label>
                    <select name="wallet_id" id="wallet_id" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
                        <option value="">Selecione</option>
                        @foreach($wallets as $wallet)
                        <option value="{{ $wallet->id }}" {{ old('wallet_id') == $wallet->id ? 'selected' : '' }}>{{ $wallet->name }}</option>
                        @endforeach
                    </select>
                    @error('wallet_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categoria</label>
                <select name="category_id" id="category_id"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
                    <option value="">Sem categoria</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição <span class="text-red-500">*</span></label>
                <input type="text" name="description" id="description" value="{{ old('description') }}" autocomplete="off" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: Supermercado, Salário, Aluguel...">
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor (R$) <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" id="amount" value="{{ old('amount') }}" step="0.01" min="0.01" autocomplete="off" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                        placeholder="0,00">
                    @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data <span class="text-red-500">*</span></label>
                    <input type="date" name="date" id="date" value="{{ old('date', date('Y-m-d')) }}" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
                    @error('date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Observações</label>
                <textarea name="notes" id="notes" rows="2" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Notas adicionais (opcional)...">{{ old('notes') }}</textarea>
            </div>

            <!-- Recurring -->
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4" x-data="{ recurring: {{ old('is_recurring') ? 'true' : 'false' }} }">
                <div class="flex items-center gap-3 mb-3">
                    <input type="checkbox" name="is_recurring" id="is_recurring" value="1" x-model="recurring"
                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                    <label for="is_recurring" class="text-sm font-medium text-gray-700 dark:text-gray-300">Transação recorrente</label>
                </div>
                <div x-show="recurring" x-cloak>
                    <label for="recurring_frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Frequência</label>
                    <select name="recurring_frequency" id="recurring_frequency"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
                        <option value="monthly" {{ old('recurring_frequency') == 'monthly' ? 'selected' : '' }}>Mensal</option>
                        <option value="weekly" {{ old('recurring_frequency') == 'weekly' ? 'selected' : '' }}>Semanal</option>
                        <option value="daily" {{ old('recurring_frequency') == 'daily' ? 'selected' : '' }}>Diário</option>
                        <option value="yearly" {{ old('recurring_frequency') == 'yearly' ? 'selected' : '' }}>Anual</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">A transação será repetida automaticamente na frequência selecionada.</p>
                </div>
            </div>

            <div>
                <label for="receipt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comprovante</label>
                <input type="file" name="receipt" id="receipt" accept=".jpg,.jpeg,.png,.pdf"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <p class="text-xs text-gray-500 mt-1">Formatos aceitos: JPG, PNG, PDF (máx. 5MB)</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('transactions.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                    <i class="fas fa-save mr-1"></i> Registrar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
