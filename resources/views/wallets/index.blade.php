@extends('layouts.app')

@section('title', 'Carteiras')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Carteiras</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Minhas Carteiras</h1>
            <p class="text-gray-600 dark:text-gray-400 text-sm">Gerencie suas contas e carteiras financeiras.</p>
        </div>
        <a href="{{ route('wallets.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
            <i class="fas fa-plus mr-2"></i> Nova Carteira
        </a>
    </div>

    <!-- Info tip -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <p class="text-sm text-blue-800 dark:text-blue-300">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>Dica:</strong> Crie carteiras separadas para cada conta bancária, cartão ou investimento. Assim você terá uma visão clara de onde está seu dinheiro.
        </p>
    </div>

    <!-- Wallets Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($wallets as $wallet)
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: {{ $wallet->color }}20">
                    <i class="fas {{ $wallet->icon }} text-xl" style="color: {{ $wallet->color }}"></i>
                </div>
                <div class="flex items-center gap-1" x-data="{ open: false }">
                    <button @click="open = !open" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div x-show="open" x-cloak @click.away="open = false" class="absolute mt-24 right-auto bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-10 w-36">
                        <a href="{{ route('wallets.show', $wallet) }}" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-eye mr-2"></i>Ver</a>
                        <a href="{{ route('wallets.edit', $wallet) }}" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-edit mr-2"></i>Editar</a>
                        <form action="{{ route('wallets.destroy', $wallet) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta carteira?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-trash mr-2"></i>Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">{{ $wallet->name }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ ucfirst($wallet->type) }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">R$ {{ number_format($wallet->balance, 2, ',', '.') }}</p>
            @if($wallet->description)
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ Str::limit($wallet->description, 60) }}</p>
            @endif
        </div>
        @empty
        <div class="col-span-full text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <i class="fas fa-wallet text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Nenhuma carteira cadastrada</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">Comece criando sua primeira carteira para organizar suas finanças.</p>
            <a href="{{ route('wallets.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-plus mr-2"></i> Criar Carteira
            </a>
        </div>
        @endforelse
    </div>
</div>
@endsection
