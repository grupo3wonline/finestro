@extends('layouts.app')

@section('title', 'Nova Meta')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('goals.index') }}" class="text-gray-500 hover:text-primary-600">Metas</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Nova Meta</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-bullseye text-primary-500 mr-2"></i> Nova Meta Financeira
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Defina um objetivo financeiro com valor alvo e prazo. Exemplo: Reserva de emergência, Viagem, Carro novo.</p>
        </div>

        <form action="{{ route('goals.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome da Meta <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" autocomplete="off" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Ex: Reserva de Emergência">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="target_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor Alvo (R$) <span class="text-red-500">*</span></label>
                    <input type="number" name="target_amount" id="target_amount" value="{{ old('target_amount') }}" step="0.01" min="0.01" autocomplete="off" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                        placeholder="10000.00">
                    @error('target_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="current_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor Atual (R$)</label>
                    <input type="number" name="current_amount" id="current_amount" value="{{ old('current_amount', '0') }}" step="0.01" min="0" autocomplete="off"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                        placeholder="0.00">
                </div>
            </div>

            <div>
                <label for="deadline" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prazo</label>
                <input type="date" name="deadline" id="deadline" value="{{ old('deadline') }}"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
                @error('deadline') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição</label>
                <textarea name="description" id="description" rows="3" autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition"
                    placeholder="Descreva sua meta e motivação...">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cor</label>
                    <input type="color" name="color" id="color" value="{{ old('color', '#10B981') }}"
                        class="w-full h-10 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer">
                </div>
                <div>
                    <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ícone</label>
                    <select name="icon" id="icon"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition">
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

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('goals.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                    <i class="fas fa-save mr-1"></i> Criar Meta
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
