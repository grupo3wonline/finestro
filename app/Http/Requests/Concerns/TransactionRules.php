<?php

namespace App\Http\Requests\Concerns;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Services\TransactionService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Regras compartilhadas entre StoreTransactionRequest e UpdateTransactionRequest.
 */
trait TransactionRules
{
    /**
     * Carteira/categoria precisam existir E pertencer ao usuário logado.
     * Antes era só "exists:wallets,id", o que aceitava o UUID de uma carteira
     * de outra pessoa (falha de autorização do tipo IDOR).
     */
    protected function ownedWalletRule(): Exists
    {
        return Rule::exists('wallets', 'id')
            ->where('user_id', $this->user()?->id)
            ->whereNull('deleted_at');
    }

    protected function ownedCategoryRule(): Exists
    {
        return Rule::exists('categories', 'id')
            ->where('user_id', $this->user()?->id)
            ->whereNull('deleted_at');
    }

    /**
     * Normaliza a entrada antes de validar:
     *  - checkbox desmarcado não é enviado pelo navegador → vira false
     *    (antes era impossível desligar a recorrência na edição);
     *  - valor digitado no formato brasileiro "1.234,56" → 1234.56.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('_has_recurring_field')) {
            $merge['is_recurring'] = $this->boolean('is_recurring');
        }

        $amount = $this->input('amount');
        if (is_string($amount) && str_contains($amount, ',')) {
            $merge['amount'] = str_replace(',', '.', str_replace('.', '', $amount));
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedRules(): array
    {
        return [
            'category_id' => ['nullable', 'uuid', $this->ownedCategoryRule()],
            'status' => ['nullable', 'string', Rule::in(TransactionStatus::values())],
            'due_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', Rule::in(PaymentMethod::values())],
            'is_recurring' => ['nullable', 'boolean'],
            'recurring_frequency' => ['nullable', 'required_if:is_recurring,1,true', 'string', 'in:daily,weekly,monthly,yearly'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'remove_receipt' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function sharedMessages(): array
    {
        return [
            'wallet_id.required' => 'Selecione uma carteira.',
            'wallet_id.exists' => 'Carteira não encontrada.',
            'category_id.exists' => 'Categoria não encontrada.',
            'type.required' => 'O tipo da transação é obrigatório.',
            'type.in' => 'Tipo de transação inválido.',
            'status.in' => 'Situação inválida.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'description.required' => 'A descrição é obrigatória.',
            'date.required' => 'A data é obrigatória.',
            'date.date' => 'Data inválida.',
            'due_date.date' => 'Data de vencimento inválida.',
            'payment_method.in' => 'Forma de pagamento inválida.',
            'recurring_frequency.required_if' => 'Informe a frequência da recorrência.',
            'installments.integer' => 'O número de parcelas deve ser um número inteiro.',
            'installments.min' => 'O mínimo é 1 parcela.',
            'installments.max' => 'O máximo é '.TransactionService::MAX_INSTALLMENTS.' parcelas.',
            'receipt.mimes' => 'O comprovante deve ser JPG, PNG ou PDF.',
            'receipt.max' => 'O comprovante não pode ter mais de 5MB.',
            'reason.max' => 'A justificativa pode ter no máximo 255 caracteres.',
        ];
    }
}
