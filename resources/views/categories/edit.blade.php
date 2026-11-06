@extends('layouts.app')

@section('title', 'Editar Categoria')

@section('breadcrumb')
<x-ui.breadcrumb :items="[
    ['label' => 'Categorias', 'url' => route('categories.index')],
    ['label' => 'Editar'],
]" />
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800">
        <div class="p-6 border-b border-surface-100 dark:border-surface-800">
            <h1 class="text-xl font-semibold text-surface-900 dark:text-white">
                <i class="fas fa-edit text-primary-500 mr-2"></i> Editar Categoria
            </h1>
            <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">Atualize as informações da categoria.</p>
        </div>

        <form autocomplete="off" action="{{ route('categories.update', $category) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">
                    Nome <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" autocomplete="off" required
                    class="w-full px-4 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">
                    Tipo <span class="text-red-500">*</span>
                </label>
                <select autocomplete="off" name="type" id="type" required
                    class="w-full px-4 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    <option value="expense" {{ old('type', $category->type) == 'expense' ? 'selected' : '' }}>Despesa</option>
                    <option value="income" {{ old('type', $category->type) == 'income' ? 'selected' : '' }}>Receita</option>
                </select>
                @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="color" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Cor</label>
                    <input type="color" name="color" id="color" value="{{ old('color', $category->color ?? '#6B7280') }}"
                        class="w-full h-10 border border-surface-200 dark:border-surface-600 rounded-lg cursor-pointer">
                </div>
                <div>
                    <label for="icon" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Ícone</label>
                    <select autocomplete="off" name="icon" id="icon"
                        class="w-full px-4 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                        <option value="fa-tag" {{ old('icon', $category->icon) == 'fa-tag' ? 'selected' : '' }}>🏷️ Tag</option>
                        <option value="fa-utensils" {{ old('icon', $category->icon) == 'fa-utensils' ? 'selected' : '' }}>🍽️ Alimentação</option>
                        <option value="fa-car" {{ old('icon', $category->icon) == 'fa-car' ? 'selected' : '' }}>🚗 Transporte</option>
                        <option value="fa-home" {{ old('icon', $category->icon) == 'fa-home' ? 'selected' : '' }}>🏠 Moradia</option>
                        <option value="fa-heartbeat" {{ old('icon', $category->icon) == 'fa-heartbeat' ? 'selected' : '' }}>❤️ Saúde</option>
                        <option value="fa-graduation-cap" {{ old('icon', $category->icon) == 'fa-graduation-cap' ? 'selected' : '' }}>🎓 Educação</option>
                        <option value="fa-gamepad" {{ old('icon', $category->icon) == 'fa-gamepad' ? 'selected' : '' }}>🎮 Lazer</option>
                        <option value="fa-shopping-bag" {{ old('icon', $category->icon) == 'fa-shopping-bag' ? 'selected' : '' }}>🛍️ Compras</option>
                        <option value="fa-briefcase" {{ old('icon', $category->icon) == 'fa-briefcase' ? 'selected' : '' }}>💼 Trabalho</option>
                        <option value="fa-money-bill" {{ old('icon', $category->icon) == 'fa-money-bill' ? 'selected' : '' }}>💵 Salário</option>
                        <option value="fa-gift" {{ old('icon', $category->icon) == 'fa-gift' ? 'selected' : '' }}>🎁 Presente</option>
                        <option value="fa-bolt" {{ old('icon', $category->icon) == 'fa-bolt' ? 'selected' : '' }}>⚡ Energia</option>
                        <option value="fa-wifi" {{ old('icon', $category->icon) == 'fa-wifi' ? 'selected' : '' }}>📶 Internet</option>
                        <option value="fa-phone" {{ old('icon', $category->icon) == 'fa-phone' ? 'selected' : '' }}>📱 Telefone</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-100 dark:border-surface-800">
                <a href="{{ route('categories.index') }}" class="px-4 py-2 text-sm font-medium text-surface-700 dark:text-surface-300 hover:bg-surface-100 dark:hover:bg-surface-800 rounded-lg transition">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                    <i class="fas fa-save mr-1"></i> Atualizar Categoria
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
