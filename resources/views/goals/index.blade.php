@extends('layouts.app')

@section('title', 'Metas Financeiras')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Metas</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Metas Financeiras</h1>
            <p class="text-gray-600 dark:text-gray-400 text-sm">Defina objetivos e acompanhe seu progresso financeiro.</p>
        </div>
        <a href="{{ route('goals.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
            <i class="fas fa-plus mr-2"></i> Nova Meta
        </a>
    </div>

    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <p class="text-sm text-blue-800 dark:text-blue-300">
            <i class="fas fa-lightbulb mr-1"></i>
            <strong>Dica:</strong> Metas com prazos definidos são 42% mais eficazes. Defina valores realistas e acompanhe semanalmente.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($goals as $goal)
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: {{ $goal->color }}20">
                    <i class="fas {{ $goal->icon }}" style="color: {{ $goal->color }}"></i>
                </div>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                    {{ $goal->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                    {{ $goal->status === 'completed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                    {{ $goal->status === 'cancelled' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400' : '' }}">
                    {{ $goal->status === 'active' ? 'Ativa' : ($goal->status === 'completed' ? 'Concluída' : 'Cancelada') }}
                </span>
            </div>

            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">{{ $goal->name }}</h3>
            @if($goal->deadline)
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Prazo: {{ $goal->deadline->format('d/m/Y') }}</p>
            @endif

            <div class="mb-2">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600 dark:text-gray-400">R$ {{ number_format($goal->current_amount, 2, ',', '.') }}</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $goal->progress }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                    <div class="h-2.5 rounded-full transition-all" style="width: {{ $goal->progress }}%; background-color: {{ $goal->color }}"></div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right">Meta: R$ {{ number_format($goal->target_amount, 2, ',', '.') }}</p>
            </div>

            <div class="flex gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('goals.edit', $goal) }}" class="flex-1 text-center px-3 py-1.5 text-xs font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition">
                    <i class="fas fa-edit mr-1"></i> Editar
                </a>
                <form action="{{ route('goals.destroy', $goal) }}" method="POST" class="flex-1" onsubmit="return confirm('Excluir esta meta?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                        <i class="fas fa-trash mr-1"></i> Excluir
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <i class="fas fa-bullseye text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Nenhuma meta cadastrada</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">Defina metas financeiras para acompanhar seu progresso.</p>
            <a href="{{ route('goals.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-plus mr-2"></i> Criar Meta
            </a>
        </div>
        @endforelse
    </div>
</div>
@endsection
