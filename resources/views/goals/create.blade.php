@extends('layouts.app')

@section('title', 'Nova Meta')

@section('breadcrumb')
<x-ui.breadcrumb :items="[
    ['label' => 'Metas', 'url' => route('goals.index')],
    ['label' => 'Nova Meta'],
]" />
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="section-card">
        <div class="p-6 border-b border-surface-100 dark:border-surface-800">
            <h1 class="text-xl font-semibold text-surface-900 dark:text-white">
                <i class="fas fa-bullseye text-primary-500 mr-2"></i> Nova Meta Financeira
            </h1>
            <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">Defina um objetivo financeiro com valor alvo e prazo. Exemplo: Reserva de emergência, Viagem, Carro novo.</p>
        </div>

        <form autocomplete="off" action="{{ route('goals.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <div>
                <label for="name" class="input-label">Nome da Meta <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" autocomplete="off" required
                    class="input" placeholder="Ex: Reserva de Emergência">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="target_amount" class="input-label">Valor Alvo (R$) <span class="text-red-500">*</span></label>
                    <input type="number" name="target_amount" id="target_amount" value="{{ old('target_amount') }}" step="0.01" min="0.01" autocomplete="off" required
                        class="input" placeholder="10000.00">
                    @error('target_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="current_amount" class="input-label">Valor Atual (R$)</label>
                    <input type="number" name="current_amount" id="current_amount" value="{{ old('current_amount', '0') }}" step="0.01" min="0" autocomplete="off"
                        class="input" placeholder="0.00">
                </div>
            </div>

            <div>
                <label for="deadline" class="input-label">Prazo</label>
                <input type="date" name="deadline" id="deadline" value="{{ old('deadline') }}" class="input" autocomplete="off">
                @error('deadline') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="input-label">Descrição</label>
                <textarea name="description" id="description" rows="3" autocomplete="off"
                    class="input" placeholder="Descreva sua meta e motivação...">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="color" class="input-label">Cor</label>
                    <input type="color" name="color" id="color" value="{{ old('color', '#039855') }}"
                        class="w-full h-10 border border-surface-200 dark:border-surface-600 rounded-xl cursor-pointer bg-white dark:bg-surface-800">
                </div>
                <div>
                    <label for="icon" class="input-label">Ícone</label>
                    <select autocomplete="off" name="icon" id="icon" class="input">
                        <option value="fa-bullseye">🎯 Alvo</option>
                        <option value="fa-piggy-bank">🐷 Poupança</option>
                        <option value="fa-plane">✈️ Viagem</option>
                        <option value="fa-car">🚗 Carro</option>
                        <option value="fa-home">🏠 Casa</option>
                        <option value="fa-graduation-cap">🎓 Educação</option>
                        <option value="fa-heart">❤️ Saúde</option>
                        <option value="fa-shield-alt">🛡️ Emergência</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-100 dark:border-surface-800">
                <a href="{{ route('goals.index') }}" class="btn-ghost">Cancelar</a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-1"></i> Criar Meta
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
