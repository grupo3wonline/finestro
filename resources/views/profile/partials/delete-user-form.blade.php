<div x-data="{ confirmDelete: false }">
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        Ao excluir sua conta, todos os seus dados, carteiras, transações e metas serão permanentemente removidos. Esta ação não pode ser desfeita.
    </p>

    <button @click="confirmDelete = true" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
        <i class="fas fa-trash mr-1"></i> Excluir Minha Conta
    </button>

    <!-- Confirmation Modal -->
    <div x-show="confirmDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="confirmDelete = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md mx-4" @click.away="confirmDelete = false">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i> Confirmar Exclusão
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Digite sua senha para confirmar a exclusão permanente da sua conta e todos os dados associados.
            </p>

            <form method="post" action="{{ route('profile.destroy') }}" class="space-y-4">
                @csrf
                @method('delete')

                <div>
                    <label for="delete_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Senha</label>
                    <input type="password" name="password" id="delete_password" autocomplete="off" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 transition"
                        placeholder="Sua senha atual">
                    @error('password', 'userDeletion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="confirmDelete = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                        <i class="fas fa-trash mr-1"></i> Excluir Permanentemente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
