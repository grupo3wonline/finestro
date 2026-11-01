@extends('layouts.app')

@section('title', 'Orçamentos')

@section('breadcrumb')
    <x-ui.breadcrumb :items="[['label' => 'Orçamentos']]" />
@endsection

@php
    $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $input = 'w-full px-3 py-2 border border-surface-200 dark:border-surface-600 rounded-lg bg-white dark:bg-surface-800 text-surface-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 transition';
    $bar = fn ($health) => match ($health) { 'exceeded' => 'bg-red-500', 'warning' => 'bg-amber-500', default => 'bg-emerald-500' };
    $usedCategoryIds = $budgets->pluck('category_id')->filter()->all();
    $hasGeneral = $budgets->contains(fn ($b) => $b->isGeneral());
@endphp

@section('content')
<div class="space-y-6" x-data="{ showForm: {{ $errors->any() ? 'true' : 'false' }}, editing: null }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-surface-900 dark:text-white">Orçamentos</h1>
            <p class="text-surface-600 dark:text-surface-400 text-sm">Defina um teto de gastos para o mês — geral ou por categoria — e acompanhe o consumo em tempo real.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form action="{{ route('budgets.copy-previous', ['month' => $month, 'year' => $year]) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-3 py-2 text-sm font-medium text-surface-600 dark:text-surface-300 hover:bg-surface-100 dark:hover:bg-surface-800 rounded-lg transition" title="Copia os orçamentos do mês anterior que ainda não existem neste mês">
                    <i class="fas fa-copy mr-2"></i> Copiar do mês anterior
                </button>
            </form>
            @if($canAddMore)
                <button type="button" @click="showForm = !showForm" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i> Novo orçamento
                </button>
            @else
                <a href="{{ route('subscription.plans') }}" class="inline-flex items-center px-4 py-2 bg-surface-200 dark:bg-surface-800 text-surface-600 dark:text-surface-300 text-sm font-medium rounded-lg">
                    <i class="fas fa-lock mr-2"></i> Limite de {{ $limit }} por mês — fazer upgrade
                </a>
            @endif
        </div>
    </div>

    {{-- Navegação por mês --}}
    <div class="flex items-center justify-between bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 px-2 py-2">
        <a href="{{ route('budgets.index', $navigation['prev']) }}" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-600 dark:text-surface-300" aria-label="Mês anterior"><i class="fas fa-chevron-left"></i></a>
        <p class="font-semibold text-surface-900 dark:text-white">{{ $navigation['label'] }}</p>
        <a href="{{ route('budgets.index', $navigation['next']) }}" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-600 dark:text-surface-300" aria-label="Próximo mês"><i class="fas fa-chevron-right"></i></a>
    </div>

    {{-- Novo orçamento --}}
    <div x-show="showForm" x-cloak class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-5">
        <form action="{{ route('budgets.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <div class="lg:col-span-2">
                <label for="category_id" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Aplicar a</label>
                <select name="category_id" id="category_id" class="{{ $input }}">
                    @unless($hasGeneral)
                        <option value="">Todas as despesas (orçamento geral)</option>
                    @endunless
                    @foreach($categories as $category)
                        @continue(in_array($category->id, $usedCategoryIds, true))
                        <option value="{{ $category->id }}" @selected(old('category_id') === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="amount" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Teto (R$)</label>
                <input type="number" name="amount" id="amount" step="0.01" min="1" required value="{{ old('amount') }}" class="{{ $input }}" placeholder="0,00" autocomplete="off">
                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="alert_threshold" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Alertar em (%)</label>
                <input type="number" name="alert_threshold" id="alert_threshold" min="10" max="100" value="{{ old('alert_threshold', 80) }}" class="{{ $input }}" autocomplete="off">
                @error('alert_threshold') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <label class="inline-flex items-center gap-2 text-sm text-surface-700 dark:text-surface-300">
                    <input type="checkbox" name="repeat_until_december" value="1" class="w-4 h-4 text-primary-600 border-surface-200 rounded">
                    Repetir até dezembro de {{ $year }}
                </label>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                    <i class="fas fa-save mr-1"></i> Salvar
                </button>
            </div>
        </form>
    </div>

    {{-- Visão geral do mês --}}
    @if($budgets->isNotEmpty())
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-surface-900 rounded-xl p-4 shadow-sm border border-surface-100 dark:border-surface-800">
                <p class="text-xs font-medium text-surface-500 uppercase">Orçado</p>
                <p class="text-xl font-bold text-surface-900 dark:text-white mt-1">{{ $brl($overview['planned']) }}</p>
            </div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-4 shadow-sm border border-surface-100 dark:border-surface-800">
                <p class="text-xs font-medium text-surface-500 uppercase">Gasto no mês</p>
                <p class="text-xl font-bold text-red-600 mt-1">{{ $brl($overview['spent']) }}</p>
                <p class="text-xs text-surface-500">{{ number_format($overview['percent'], 0) }}% do orçado</p>
            </div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-4 shadow-sm border border-surface-100 dark:border-surface-800">
                <p class="text-xs font-medium text-surface-500 uppercase">{{ $overview['remaining'] >= 0 ? 'Disponível' : 'Estourado' }}</p>
                <p class="text-xl font-bold mt-1 {{ $overview['remaining'] >= 0 ? 'text-primary-600' : 'text-red-600' }}">{{ $brl(abs($overview['remaining'])) }}</p>
            </div>
            <div class="bg-white dark:bg-surface-900 rounded-xl p-4 shadow-sm border border-surface-100 dark:border-surface-800">
                <p class="text-xs font-medium text-surface-500 uppercase">Fora do orçamento</p>
                <p class="text-xl font-bold text-surface-900 dark:text-white mt-1">{{ $brl($overview['unbudgeted']) }}</p>
                <p class="text-xs text-surface-500">gastos em categorias sem teto</p>
            </div>
        </div>
    @endif

    {{-- Lista --}}
    <div class="grid md:grid-cols-2 gap-4">
        @forelse($budgets as $budget)
            <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border p-5 {{ $budget->health === 'exceeded' ? 'border-red-300 dark:border-red-800' : ($budget->health === 'warning' ? 'border-amber-300 dark:border-amber-800' : 'border-surface-100 dark:border-surface-800') }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: {{ $budget->category->color ?? '#3B82F6' }}20">
                            <i class="fas {{ $budget->category->icon ?? 'fa-scale-balanced' }}" style="color: {{ $budget->category->color ?? '#3B82F6' }}"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-surface-900 dark:text-white">{{ $budget->label }}</h3>
                            <p class="text-xs text-surface-500">Alerta em {{ $budget->alert_threshold }}%</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="editing = editing === '{{ $budget->id }}' ? null : '{{ $budget->id }}'" class="p-1.5 rounded hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-500" title="Editar"><i class="fas fa-edit text-sm"></i></button>
                        <form action="{{ route('budgets.destroy', $budget) }}" method="POST" onsubmit="return confirm('Remover este orçamento?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 rounded hover:bg-surface-100 dark:hover:bg-surface-800 text-red-500" title="Remover"><i class="fas fa-trash text-sm"></i></button>
                        </form>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-surface-600 dark:text-surface-300">{{ $brl($budget->spent) }} de {{ $brl($budget->amount) }}</span>
                        <span class="font-semibold {{ $budget->health === 'exceeded' ? 'text-red-600' : ($budget->health === 'warning' ? 'text-amber-600' : 'text-primary-600') }}">{{ number_format($budget->percent, 0) }}%</span>
                    </div>
                    <div class="w-full h-2.5 rounded-full bg-surface-200 dark:bg-surface-800 overflow-hidden" role="progressbar" aria-valuenow="{{ min(100, $budget->percent) }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full transition-all {{ $bar($budget->health) }}" style="width: {{ min(100, $budget->percent) }}%"></div>
                    </div>
                    <p class="text-xs mt-2 {{ $budget->remaining < 0 ? 'text-red-600' : 'text-surface-500' }}">
                        @if($budget->remaining < 0)
                            <i class="fas fa-triangle-exclamation mr-1"></i> Estourou em {{ $brl(abs($budget->remaining)) }}
                        @else
                            Restam {{ $brl($budget->remaining) }}
                        @endif
                    </p>
                </div>

                <form x-show="editing === '{{ $budget->id }}'" x-cloak action="{{ route('budgets.update', $budget) }}" method="POST" class="mt-4 pt-4 border-t border-surface-100 dark:border-surface-800 grid grid-cols-2 gap-3 items-end">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-xs font-medium text-surface-500 mb-1">Teto (R$)</label>
                        <input type="number" name="amount" step="0.01" min="1" required value="{{ $budget->amount }}" autocomplete="off" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-surface-500 mb-1">Alertar em (%)</label>
                        <input type="number" name="alert_threshold" min="10" max="100" value="{{ $budget->alert_threshold }}" autocomplete="off" class="{{ $input }}">
                    </div>
                    <label class="col-span-2 inline-flex items-center gap-2 text-xs text-surface-600 dark:text-surface-300">
                        <input type="checkbox" name="repeat_until_december" value="1" class="w-4 h-4 text-primary-600 border-surface-200 rounded">
                        Aplicar este valor também aos meses seguintes (até dezembro)
                    </label>
                    <div class="col-span-2 flex justify-end">
                        <button type="submit" class="px-4 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-medium rounded-lg transition">Salvar</button>
                    </div>
                </form>
            </div>
        @empty
            <div class="md:col-span-2 bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 text-center py-16 px-4">
                <i class="fas fa-scale-balanced text-5xl text-surface-300 dark:text-surface-600 mb-4"></i>
                <h3 class="text-lg font-medium text-surface-900 dark:text-white mb-2">Nenhum orçamento para {{ mb_strtolower($navigation['label']) }}</h3>
                <p class="text-surface-500 dark:text-surface-400 text-sm max-w-md mx-auto mb-4">
                    Exemplo: teto de R$ 1.200 para Alimentação. Quando os gastos chegarem a 80%, o Finestro avisa no dashboard.
                </p>
                @if($canAddMore)
                    <button type="button" @click="showForm = true" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i> Criar primeiro orçamento
                    </button>
                @endif
            </div>
        @endforelse
    </div>

    <p class="text-xs text-surface-500 dark:text-surface-400">
        <i class="fas fa-circle-info mr-1"></i>
        O consumo considera todas as despesas do mês (pagas e pendentes), pela data da transação. Cada parcela conta no mês em que cai.
    </p>
</div>
@endsection
