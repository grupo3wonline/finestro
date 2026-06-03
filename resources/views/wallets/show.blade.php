@extends('layouts.app')

@section('title', $wallet->name)

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('wallets.index') }}" class="text-gray-500 hover:text-primary-600">Carteiras</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">{{ $wallet->name }}</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center" style="background-color: {{ $wallet->color }}20">
                    <i class="fas {{ $wallet->icon }} text-2xl" style="color: {{ $wallet->color }}"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $wallet->name }}</h1>
                    <p class="text-gray-500 dark:text-gray-400">{{ ucfirst($wallet->type) }} • {{ $wallet->currency }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('wallets.edit', $wallet) }}" class="px-4 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition">
                    <i class="fas fa-edit mr-1"></i> Editar
                </a>
            </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Saldo Atual</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">R$ {{ number_format($wallet->balance, 2, ',', '.') }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                <p class="text-lg font-medium {{ $wallet->is_active ? 'text-green-600' : 'text-red-600' }}">
                    <i class="fas {{ $wallet->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                    {{ $wallet->is_active ? 'Ativa' : 'Inativa' }}
                </p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Criada em</p>
                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ $wallet->created_at->format('d/m/Y') }}</p>
            </div>
        </div>

        @if($wallet->description)
        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $wallet->description }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
