@extends('layouts.app')

@section('title', 'Editar Meta')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-surface-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-surface-400 text-xs"></i></li>
        <li><a href="{{ route('goals.index') }}" class="text-surface-500 hover:text-primary-600">Metas</a></li>
        <li><i class="fas fa-chevron-right text-surface-400 text-xs"></i></li>
        <li class="text-surface-700 dark:text-surface-300 font-medium">Editar</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="section-card">
        <div class="p-6 border-b border-surface-100 dark:border-surface-800">
            <h1 class="text-xl font-semibold text-surface-900 dark:text-white"><i class="fas fa-edit text-primary-500 mr-2"></i> Editar Meta</h1>
        </div>

        <form action="{{ route('goals.update', $goal) }}" method="POST" class="p-6 space-y-6">
            @csrf @method('PUT')

            <div>
                <label for="name" class="input-label">Nome <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $goal->name) }}" autocomplete="off" required class="input">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="target_amount" class="input-label">Valor Alvo (R$) <span class="text-red-500">*</span></label>
                    <input type="number" name="target_amount" id="target_amount" value="{{ old('target_amount', $goal->target_amount) }}" step="0.01" min="0.01" autocomplete="off" required class="input">
                </div>
                <div>
                    <label for="current_amount" class="input-label">Valor Atual (R$)</label>
                    <input type="number" name="current_amount" id="current_amount" value="{{ old('current_amount', $goal->current_amount) }}" step="0.01" min="0" autocomplete="off" class="input">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="deadline" class="input-label">Prazo</label>
                    <input type="date" name="deadline" id="deadline" value="{{ old('deadline', $goal->deadline?->format('Y-m-d')) }}" class="input">
                </div>
                <div>
                    <label for="status" class="input-label">Status</label>
                    <select name="status" id="status" class="input">
                        <option value="active" {{ $goal->status == 'active' ? 'selected' : '' }}>Ativa</option>
                        <option value="completed" {{ $goal->status == 'completed' ? 'selected' : '' }}>Concluída</option>
                        <option value="cancelled" {{ $goal->status == 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="description" class="input-label">Descrição</label>
                <textarea name="description" id="description" rows="3" autocomplete="off" class="input">{{ old('description', $goal->description) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-100 dark:border-surface-800">
                <a href="{{ route('goals.index') }}" class="btn-ghost">Cancelar</a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-1"></i> Atualizar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
