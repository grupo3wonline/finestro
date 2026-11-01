@extends('layouts.app')

@section('title', 'Categorias')

@section('breadcrumb')
    <x-ui.breadcrumb :items="[['label' => 'Categorias']]" />
@endsection

@section('content')
<div class="space-y-6">
    <x-ui.page-header title="Categorias" subtitle="Organize suas transações por categorias personalizadas." icon="fa-tags">
        <x-slot:actions>
            <form action="{{ route('categories.import-suggested') }}" method="POST">
                @csrf
                <x-ui.button type="submit" variant="outline" icon="fa-wand-magic-sparkles"
                    title="Adiciona categorias prontas (Alimentação, Moradia, Saúde, Salário...) que você ainda não tem">
                    Importar sugeridas
                </x-ui.button>
            </form>
            <x-ui.button :href="route('categories.create')" icon="fa-plus">Nova Categoria</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.tip>
        <strong>Dica:</strong> Crie categorias como "Alimentação", "Transporte", "Lazer" para ter uma visão clara de onde seu dinheiro está indo.
    </x-ui.tip>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($categories as $category)
        <div class="bg-white dark:bg-surface-900 rounded-xl p-5 shadow-sm border border-surface-100 dark:border-surface-800 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: {{ $category->color ?? '#6B7280' }}20">
                        <i class="fas {{ $category->icon ?? 'fa-tag' }}" style="color: {{ $category->color ?? '#6B7280' }}"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-surface-900 dark:text-white text-sm">{{ $category->name }}</h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $category->type === 'income' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                            {{ $category->type === 'income' ? 'Receita' : 'Despesa' }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <a href="{{ route('categories.edit', $category) }}" class="p-1.5 rounded hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-500" title="Editar">
                        <i class="fas fa-edit text-sm"></i>
                    </a>
                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Excluir esta categoria?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 rounded hover:bg-surface-100 dark:hover:bg-surface-800 text-red-500" title="Excluir">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </form>
                </div>
            </div>
            <p class="text-xs text-surface-500 dark:text-surface-400">{{ $category->transactions_count ?? 0 }} transação(ões)</p>
        </div>
        @empty
        <div class="col-span-full text-center py-16 bg-white dark:bg-surface-900 rounded-xl border border-surface-100 dark:border-surface-800">
            <i class="fas fa-tags text-5xl text-surface-300 dark:text-surface-600 mb-4"></i>
            <h3 class="text-lg font-medium text-surface-900 dark:text-white mb-2">Nenhuma categoria cadastrada</h3>
            <p class="text-surface-500 dark:text-surface-400 mb-4">Crie categorias para organizar melhor suas transações.</p>
            <a href="{{ route('categories.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-plus mr-2"></i> Criar Categoria
            </a>
        </div>
        @endforelse
    </div>
</div>
@endsection
