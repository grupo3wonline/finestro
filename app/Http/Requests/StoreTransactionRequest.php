<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TransactionRules;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    use TransactionRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Transaction::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge($this->sharedRules(), [
            'wallet_id' => ['required', 'uuid', $this->ownedWalletRule()],
            'type' => ['required', 'string', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999'],
            'description' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:'.TransactionService::MAX_INSTALLMENTS],
        ]);
    }

    /**
     * Parcelamento e recorrência são mutuamente exclusivos
     * (só faz sentido bloquear quando há mais de 1 parcela).
     *
     * @param  Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ((int) $this->input('installments', 1) > 1 && $this->boolean('is_recurring')) {
                $validator->errors()->add('installments', 'Uma transação não pode ser parcelada e recorrente ao mesmo tempo.');
            }
        });
    }

    public function messages(): array
    {
        return $this->sharedMessages();
    }
}
