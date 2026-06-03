@extends('layouts.app')

@section('title', $goal->name)

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('goals.index') }}" class="text-gray-500 hover:text-primary-600">Metas</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">{{ $goal->name }}</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center" style="background-color: {{ $goal->color }}20">
                <i class="fas {{ $goal->icon }} text-2xl" style="color: {{ $goal->color }}"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $goal->name }}</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                    {{ $goal->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $goal->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}
                    {{ $goal->status === 'cancelled' ? 'bg-gray-100 text-gray-800' : '' }}">
                    {{ $goal->status === 'active' ? 'Ativa' : ($goal->status === 'completed' ? 'Concluída' : 'Cancelada') }}
                </span>
            </div>
        </div>

        <!-- Progress -->
        <div class="mb-6">
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-600 dark:text-gray-400">Progresso</span>
                <span class="font-bold text-gray-900 dark:text-white">{{ $goal->progress }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                <div class="h-4 rounded-full transition-all" style="width: {{ $goal->progress }}%; background-color: {{ $goal->color }}"></div>
            </div>
            <div class="flex justify-between mt-2 text-sm">
                <span class="text-gray-500">R$ {{ number_format($goal->current_amount, 2, ',', '.') }}</span>
                <span class="text-gray-500">R$ {{ number_format($goal->target_amount, 2, ',', '.') }}</span>
            </div>
        </div>

        @if($goal->deadline)
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Prazo</p>
            <p class="text-lg font-medium text-gray-900 dark:text-white">{{ $goal->deadline->format('d/m/Y') }}</p>
        </div>
        @endif

        @if($goal->description)
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Descrição</p>
            <p class="text-gray-700 dark:text-gray-300">{{ $goal->description }}</p>
        </div>
        @endif

        <div class="flex gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('goals.edit', $goal) }}" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-edit mr-1"></i> Editar
            </a>
            <a href="{{ route('goals.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">Voltar</a>
        </div>
    </div>
</div>
@endsection
