@extends('layouts.app')

@section('title', 'Metas Financeiras')

@section('breadcrumb')
    <x-ui.breadcrumb :items="[['label' => 'Metas']]" />
@endsection

@section('content')
<div class="space-y-6">
    <x-ui.page-header title="Metas Financeiras" subtitle="Defina objetivos e acompanhe seu progresso financeiro." icon="fa-bullseye">
        <x-slot:actions>
            <x-ui.button :href="route('goals.create')" icon="fa-plus">Nova Meta</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.tip>
        <strong>Dica:</strong> Metas com prazos definidos são 42% mais eficazes. Defina valores realistas e acompanhe semanalmente.
    </x-ui.tip>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($goals as $goal)
        <div class="bg-white dark:bg-surface-900 rounded-2xl p-6 shadow-card border border-surface-100 dark:border-surface-800 hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background-color: {{ $goal->color }}20">
                    <i class="fas {{ $goal->icon }}" style="color: {{ $goal->color }}"></i>
                </div>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                    {{ $goal->status === 'active' ? 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-400' : '' }}
                    {{ $goal->status === 'completed' ? 'bg-accent-100 text-accent-800 dark:bg-accent-900/30 dark:text-accent-400' : '' }}
                    {{ $goal->status === 'cancelled' ? 'bg-surface-100 text-surface-700 dark:bg-surface-700 dark:text-surface-400' : '' }}">
                    {{ $goal->status === 'active' ? 'Ativa' : ($goal->status === 'completed' ? 'Concluída' : 'Cancelada') }}
                </span>
            </div>

            <h3 class="font-semibold text-surface-900 dark:text-white mb-1">{{ $goal->name }}</h3>
            @if($goal->deadline)
            <p class="text-xs text-surface-500 dark:text-surface-400 mb-3">Prazo: {{ $goal->deadline->format('d/m/Y') }}</p>
            @endif

            <div class="mb-2">
                <div class="flex justify-between text-sm mb-1">
                    <span class="num text-surface-600 dark:text-surface-400">R$ {{ number_format($goal->current_amount, 2, ',', '.') }}</span>
                    <span class="num font-medium text-surface-900 dark:text-white">{{ $goal->progress }}%</span>
                </div>
                <div class="w-full bg-surface-200 dark:bg-surface-700 rounded-full h-2.5">
                    <div class="h-2.5 rounded-full transition-all" style="width: {{ $goal->progress }}%; background-color: {{ $goal->color }}"></div>
                </div>
                <p class="num text-xs text-surface-500 dark:text-surface-400 mt-1 text-right">Meta: R$ {{ number_format($goal->target_amount, 2, ',', '.') }}</p>
            </div>

            <div class="flex gap-2 mt-4 pt-4 border-t border-surface-100 dark:border-surface-800">
                <a href="{{ route('goals.edit', $goal) }}" class="flex-1 text-center px-3 py-1.5 text-xs font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition">
                    <i class="fas fa-edit mr-1"></i> Editar
                </a>
                <form autocomplete="off" action="{{ route('goals.destroy', $goal) }}" method="POST" class="flex-1" data-confirm="Excluir esta meta?">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                        <i class="fas fa-trash mr-1"></i> Excluir
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-16 bg-white dark:bg-surface-900 rounded-2xl border border-surface-100 dark:border-surface-800">
            <i class="fas fa-bullseye text-5xl text-surface-300 dark:text-surface-600 mb-4"></i>
            <h3 class="text-lg font-medium text-surface-900 dark:text-white mb-2">Nenhuma meta cadastrada</h3>
            <p class="text-surface-500 dark:text-surface-400 mb-4">Defina metas financeiras para acompanhar seu progresso.</p>
            <a href="{{ route('goals.create') }}" class="btn-primary">
                <i class="fas fa-plus mr-2"></i> Criar Meta
            </a>
        </div>
        @endforelse
    </div>
</div>
@endsection
