@extends('layouts.app')

@section('title', $goal->name)

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-surface-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-surface-400 text-xs"></i></li>
        <li><a href="{{ route('goals.index') }}" class="text-surface-500 hover:text-primary-600">Metas</a></li>
        <li><i class="fas fa-chevron-right text-surface-400 text-xs"></i></li>
        <li class="text-surface-700 dark:text-surface-300 font-medium">{{ $goal->name }}</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-6">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center" style="background-color: {{ $goal->color }}20">
                <i class="fas {{ $goal->icon }} text-2xl" style="color: {{ $goal->color }}"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-surface-900 dark:text-white">{{ $goal->name }}</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                    {{ $goal->status === 'active' ? 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-400' : '' }}
                    {{ $goal->status === 'completed' ? 'bg-accent-100 text-accent-800 dark:bg-accent-900/30 dark:text-accent-400' : '' }}
                    {{ $goal->status === 'cancelled' ? 'bg-surface-100 text-surface-700 dark:bg-surface-700 dark:text-surface-400' : '' }}">
                    {{ $goal->status === 'active' ? 'Ativa' : ($goal->status === 'completed' ? 'Concluída' : 'Cancelada') }}
                </span>
            </div>
        </div>

        <!-- Progress -->
        <div class="mb-6">
            <div class="flex justify-between text-sm mb-2">
                <span class="text-surface-600 dark:text-surface-400">Progresso</span>
                <span class="num font-bold text-surface-900 dark:text-white">{{ $goal->progress }}%</span>
            </div>
            <div class="w-full bg-surface-200 dark:bg-surface-700 rounded-full h-4">
                <div class="h-4 rounded-full transition-all" style="width: {{ $goal->progress }}%; background-color: {{ $goal->color }}"></div>
            </div>
            <div class="flex justify-between mt-2 text-sm">
                <span class="num text-surface-500">R$ {{ number_format($goal->current_amount, 2, ',', '.') }}</span>
                <span class="num text-surface-500">R$ {{ number_format($goal->target_amount, 2, ',', '.') }}</span>
            </div>
        </div>

        @if($goal->deadline)
        <div class="bg-surface-50 dark:bg-surface-800/60 rounded-xl p-4 mb-4">
            <p class="text-sm text-surface-500 dark:text-surface-400">Prazo</p>
            <p class="num text-lg font-medium text-surface-900 dark:text-white">{{ $goal->deadline->format('d/m/Y') }}</p>
        </div>
        @endif

        @if($goal->description)
        <div class="bg-surface-50 dark:bg-surface-800/60 rounded-xl p-4 mb-4">
            <p class="text-sm text-surface-500 dark:text-surface-400 mb-1">Descrição</p>
            <p class="text-surface-700 dark:text-surface-300">{{ $goal->description }}</p>
        </div>
        @endif

        <div class="flex gap-3 mt-6 pt-4 border-t border-surface-100 dark:border-surface-800">
            <a href="{{ route('goals.edit', $goal) }}" class="btn-primary">
                <i class="fas fa-edit mr-1"></i> Editar
            </a>
            <a href="{{ route('goals.index') }}" class="btn-ghost">Voltar</a>
        </div>
    </div>
</div>
@endsection
