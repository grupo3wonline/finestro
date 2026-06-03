@extends('layouts.app')

@section('title', 'Categorias')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Categorias</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Categorias</h1>
            <p class="text-gray-600 dark:text-gray-400 text-sm">Organize suas transações por categorias personalizadas.</p>
        </div>
        <a href="{{ route('categories.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
            <i class="fas fa-plus mr-2"></i> Nova Categoria
        </a>
    </div>

    <!-- Info tip -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <p class="text-sm text-blue-800 dark:text-blue-300">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>Dica:</strong> Crie categorias como "Alimentação", "Transporte", "Lazer" para ter uma visão clara de onde seu dinheiro está indo.
        </p>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($categories as $category)
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: {{ $category->color ?? '#6B7280' }}20">
                        <i class="fas {{ $category->icon ?? 'fa-tag' }}" style="color: {{ $category->color ?? '#6B7280' }}"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm">{{ $category->name }}</h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $category->type === 'income' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                            {{ $category->type === 'income' ? 'Receita' : 'Despesa' }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <a href="{{ route('categories.edit', $category) }}" class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500" title="Editar">
                        <i class="fas fa-edit text-sm"></i>
                    </a>
                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Excluir esta categoria?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-red-500" title="Excluir">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </form>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $category->transactions_count ?? 0 }} transação(ões)</p>
        </div>
        @empty
        <div class="col-span-full text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <i class="fas fa-tags text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Nenhuma categoria cadastrada</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">Crie categorias para organizar melhor suas transações.</p>
            <a href="{{ route('categories.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-plus mr-2"></i> Criar Categoria
            </a>
        </div>
        @endforelse
    </div>
</div>
@endsection
