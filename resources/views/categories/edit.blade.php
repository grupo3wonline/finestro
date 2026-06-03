@extends('layouts.app')

@section('title', 'Editar Categoria')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('categories.index') }}" class="text-gray-500 hover:text-primary-600">Categorias</a></li>
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
                <i class="fas fa-edit text-primary-500 mr-2"></i> Editar Categoria
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Atualize as informações da categoria.</p>
        </div>

        <form action="{{ route('categories.update', $category) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Nome <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" autocomplete="off" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Tipo <span class="text-red-500">*</span>
                </label>
                <select name="type" id="type" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    <option value="expense" {{ old('type', $category->type) == 'expense' ? 'selected' : '' }}>Despesa</option>
                    <option value="income" {{ old('type', $category->type) == 'income' ? 'selected' : '' }}>Receita</option>
                </select>
                @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cor</label>
                    <input type="color" name="color" id="color" value="{{ old('color', $category->color ?? '#6B7280') }}"
                        class="w-full h-10 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer">
                </div>
                <div>
                    <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ícone</label>
                    <select name="icon" id="icon"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
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

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('categories.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
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
