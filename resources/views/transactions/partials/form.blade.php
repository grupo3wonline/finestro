{{--
    Formulário compartilhado de transação (criar e editar).

    Variáveis:
      $transaction    ?Transaction  (null na criação)
      $wallets        Collection
      $categories     Collection
      $paymentMethods array value => label
      $defaults       array (apenas criação: type, status, wallet_id)
--}}
@php
    $isEdit = isset($transaction) && $transaction;
    $defaults = $defaults ?? [];
    $input = 'input';
    $label = 'input-label';

    $initial = [
        'type' => old('type', $isEdit ? $transaction->type : ($defaults['type'] ?? 'expense')),
        'status' => old('status', $isEdit ? $transaction->status : ($defaults['status'] ?? 'paid')),
        'category_id' => old('category_id', $isEdit ? $transaction->category_id : ''),
        'amount' => old('amount', $isEdit ? $transaction->amount : ''),
        'installments' => (int) old('installments', 1),
        'recurring' => (bool) old('is_recurring', $isEdit ? $transaction->is_recurring : false),
    ];

    $categoriesJson = $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'type' => $c->type])->values();
@endphp

<div x-data="transactionForm(@js($initial), @js($categoriesJson))" class="space-y-6">

    {{-- Tipo + Carteira --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="{{ $label }}">Tipo <span class="text-red-500">*</span></label>
            <div class="grid grid-cols-2 gap-2" role="radiogroup">
                <label class="flex items-center justify-center gap-2 px-3 py-2 rounded-xl border cursor-pointer text-sm font-medium transition"
                    :class="type === 'expense' ? 'border-red-500 bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300' : 'border-surface-200 dark:border-surface-600 text-surface-600 dark:text-surface-300'">
                    <input type="radio" name="type" value="expense" x-model="type" class="sr-only">
                    <i class="fas fa-arrow-down"></i> Despesa
                </label>
                <label class="flex items-center justify-center gap-2 px-3 py-2 rounded-xl border cursor-pointer text-sm font-medium transition"
                    :class="type === 'income' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' : 'border-surface-200 dark:border-surface-600 text-surface-600 dark:text-surface-300'">
                    <input type="radio" name="type" value="income" x-model="type" class="sr-only">
                    <i class="fas fa-arrow-up"></i> Receita
                </label>
            </div>
            @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="wallet_id" class="{{ $label }}">Carteira <span class="text-red-500">*</span></label>
            <select name="wallet_id" id="wallet_id" required class="{{ $input }}">
                <option value="">Selecione</option>
                @foreach($wallets as $wallet)
                    <option value="{{ $wallet->id }}" @selected(old('wallet_id', $isEdit ? $transaction->wallet_id : ($defaults['wallet_id'] ?? null)) == $wallet->id)>
                        {{ $wallet->name }}{{ $wallet->isCreditCard() ? ' (cartão)' : '' }}
                    </option>
                @endforeach
            </select>
            @error('wallet_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Descrição + Categoria --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="description" class="{{ $label }}">Descrição <span class="text-red-500">*</span></label>
            <input type="text" name="description" id="description" required maxlength="255" autocomplete="off"
                value="{{ old('description', $isEdit ? $transaction->description : '') }}"
                class="{{ $input }}" placeholder="Ex.: Supermercado, Salário, Aluguel...">
            @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="category_id" class="{{ $label }}">
                Categoria
                <a href="{{ route('categories.index') }}" class="ml-1 text-xs text-primary-600 hover:underline font-normal">gerenciar</a>
            </label>
            <select name="category_id" id="category_id" x-model="categoryId" class="{{ $input }}">
                <option value="">Sem categoria</option>
                <template x-for="category in filteredCategories" :key="category.id">
                    <option :value="category.id" x-text="category.name" :selected="category.id === categoryId"></option>
                </template>
            </select>
            @error('category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Valor + Data --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="amount" class="{{ $label }}">
                <span x-text="installments > 1 ? 'Valor TOTAL da compra (R$)' : 'Valor (R$)'">Valor (R$)</span>
                <span class="text-red-500">*</span>
            </label>
            <input type="number" name="amount" id="amount" step="0.01" min="0.01" required autocomplete="off"
                x-model="amount" class="{{ $input }}" placeholder="0,00">
            @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="date" class="{{ $label }}">
                Data <span class="text-red-500">*</span>
                <i class="fas fa-circle-question text-surface-400 ml-1" title="Data de competência: o mês em que a transação entra nos relatórios e orçamentos."></i>
            </label>
            <input type="date" name="date" id="date" required autocomplete="off"
                value="{{ old('date', $isEdit ? $transaction->date->format('Y-m-d') : date('Y-m-d')) }}" class="{{ $input }}">
            @error('date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Situação + Vencimento + Forma de pagamento --}}
    <div class="bg-surface-50 dark:bg-surface-800/40 rounded-xl p-4 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="status" class="{{ $label }}">Situação</label>
                <select name="status" id="status" x-model="status" class="{{ $input }}">
                    <option value="paid" x-text="type === 'income' ? 'Recebido' : 'Pago'">Pago</option>
                    <option value="pending" x-text="type === 'income' ? 'A receber' : 'A pagar'">A pagar</option>
                </select>
                @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="due_date" class="{{ $label }}">Vencimento</label>
                <input type="date" name="due_date" id="due_date" autocomplete="off"
                    value="{{ old('due_date', $isEdit ? $transaction->due_date?->format('Y-m-d') : '') }}" class="{{ $input }}">
                @error('due_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="payment_method" class="{{ $label }}">Forma de pagamento</label>
                <select name="payment_method" id="payment_method" class="{{ $input }}">
                    <option value="">Não informada</option>
                    @foreach($paymentMethods as $value => $name)
                        <option value="{{ $value }}" @selected(old('payment_method', $isEdit ? $transaction->payment_method : '') === $value)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('payment_method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-xs text-surface-500 dark:text-surface-400" x-show="status === 'pending'" x-cloak>
            <i class="fas fa-circle-info mr-1"></i>
            Contas pendentes <strong>não alteram o saldo</strong> da carteira até serem marcadas como pagas. Elas aparecem em
            <a href="{{ route('bills.index') }}" class="text-primary-600 hover:underline">Contas a pagar/receber</a>.
        </p>
    </div>

    {{-- Parcelamento (somente criação) + Recorrência --}}
    <div class="bg-surface-50 dark:bg-surface-800/40 rounded-xl p-4 space-y-4">
        @unless($isEdit)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label for="installments" class="{{ $label }}">Parcelas</label>
                    <input type="number" name="installments" id="installments" min="1" max="120" x-model.number="installments"
                        :disabled="recurring" class="{{ $input }} disabled:opacity-50" autocomplete="off">
                    @error('installments') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2 text-sm text-surface-600 dark:text-surface-300" x-show="installments > 1" x-cloak>
                    <i class="fas fa-layer-group text-primary-500 mr-1"></i>
                    <span x-text="installmentPreview"></span>
                    <p class="text-xs text-surface-500 mt-1">Uma parcela por mês a partir da data informada. Em cartão de crédito, todas consomem o limite na hora (como no banco).</p>
                </div>
            </div>
        @endunless

        <input type="hidden" name="_has_recurring_field" value="1">
        <div class="flex items-center gap-3">
            <input type="checkbox" name="is_recurring" id="is_recurring" value="1" x-model="recurring" :disabled="installments > 1"
                class="w-4 h-4 text-primary-600 border-surface-300 rounded focus:ring-primary-500 disabled:opacity-50">
            <label for="is_recurring" class="text-sm font-medium text-surface-700 dark:text-surface-300">
                Transação recorrente <span class="text-xs font-normal text-surface-500">(aluguel, salário, assinaturas...)</span>
            </label>
        </div>
        <div x-show="recurring" x-cloak>
            <label for="recurring_frequency" class="{{ $label }}">Frequência</label>
            @php $freq = old('recurring_frequency', $isEdit ? ($transaction->recurring_frequency ?? 'monthly') : 'monthly'); @endphp
            <select name="recurring_frequency" id="recurring_frequency" class="{{ $input }}">
                <option value="monthly" @selected($freq === 'monthly')>Mensal</option>
                <option value="weekly" @selected($freq === 'weekly')>Semanal</option>
                <option value="daily" @selected($freq === 'daily')>Diária</option>
                <option value="yearly" @selected($freq === 'yearly')>Anual</option>
            </select>
            <p class="text-xs text-surface-500 mt-1">
                As próximas ocorrências são criadas automaticamente como <strong>pendentes</strong> até 7 dias antes do vencimento
                (débito automático e cartão entram como pagas na data).
                @if($isEdit) Para encerrar a série, desmarque esta opção. @endif
            </p>
            @error('recurring_frequency') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Observações + Comprovante --}}
    <div>
        <label for="notes" class="{{ $label }}">Observações</label>
        <textarea name="notes" id="notes" rows="2" maxlength="1000" class="{{ $input }}"
            placeholder="Notas adicionais (opcional)...">{{ old('notes', $isEdit ? $transaction->notes : '') }}</textarea>
        @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="receipt" class="{{ $label }}">Comprovante</label>
        @if($isEdit && $transaction->hasReceipt())
            <p class="text-xs text-surface-600 dark:text-surface-400 mb-2">
                <i class="fas fa-paperclip mr-1"></i> Já existe um comprovante anexado.
                <a href="{{ route('transactions.receipt', $transaction) }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline">Ver comprovante</a>
                — envie outro arquivo para substituí-lo.
            </p>
            <div class="flex items-start gap-2 mb-2">
                <input type="checkbox" name="remove_receipt" value="1" id="remove_receipt" autocomplete="off" @checked(old('remove_receipt'))
                    class="mt-0.5 rounded border-surface-300 dark:border-surface-600 text-primary-600 focus-visible:ring-primary-500">
                <label for="remove_receipt" class="text-sm text-surface-700 dark:text-surface-300">
                    Remover comprovante atual
                    <span class="block text-xs text-surface-500 dark:text-surface-400">O arquivo é removido ao salvar. Se você também enviar um novo arquivo, ele passa a ser o comprovante.</span>
                </label>
            </div>
        @endif
        <input type="file" name="receipt" id="receipt" accept=".jpg,.jpeg,.png,.pdf"
            class="w-full px-4 py-2 border border-surface-200 dark:border-surface-600 rounded-xl bg-white dark:bg-surface-800 text-surface-900 dark:text-white text-sm">
        <p class="text-xs text-surface-500 mt-1">JPG, PNG ou PDF (máx. 5MB).</p>
        @error('receipt') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    @if($isEdit)
        <div>
            <label for="reason" class="{{ $label }}">Motivo da alteração <span class="text-xs font-normal text-surface-500">(opcional — fica no histórico)</span></label>
            <input type="text" name="reason" id="reason" maxlength="255" value="{{ old('reason') }}" class="{{ $input }}"
                placeholder="Ex.: valor corrigido conforme nota fiscal" autocomplete="off">
            @error('reason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    @endif
</div>

@once
@push('scripts')
<script>
    function transactionForm(initial, categories) {
        return {
            type: initial.type,
            status: initial.status,
            categoryId: initial.category_id || '',
            amount: initial.amount,
            installments: initial.installments || 1,
            recurring: initial.recurring,
            categories,

            init() {
                // Ao trocar o tipo, limpa a categoria se ela não pertence ao novo tipo.
                this.$watch('type', () => {
                    if (!this.filteredCategories.some(c => c.id === this.categoryId)) {
                        this.categoryId = '';
                    }
                });
            },

            get filteredCategories() {
                return this.categories.filter(c => c.type === this.type);
            },

            // Mesma regra do servidor: divide em centavos, sobra vai para a última parcela.
            get installmentPreview() {
                const n = parseInt(this.installments, 10);
                const total = Math.round(parseFloat(this.amount || 0) * 100);
                if (!n || n < 2 || !total) return '';
                const base = Math.floor(total / n);
                const last = total - base * (n - 1);
                const fmt = c => (c / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                return base === last
                    ? `${n}x de ${fmt(base)}`
                    : `${n - 1}x de ${fmt(base)} + última de ${fmt(last)}`;
            },
        };
    }
</script>
@endpush
@endonce
