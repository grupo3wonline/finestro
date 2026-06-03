<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'uuid', 'exists:wallets,id'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'type' => ['required', 'string', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurring_frequency' => ['nullable', 'string', 'in:daily,weekly,monthly,yearly'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.required' => 'Selecione uma carteira.',
            'wallet_id.exists' => 'Carteira não encontrada.',
            'type.required' => 'O tipo da transação é obrigatório.',
            'type.in' => 'Tipo de transação inválido.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'description.required' => 'A descrição é obrigatória.',
            'date.required' => 'A data é obrigatória.',
            'date.date' => 'Data inválida.',
            'receipt.mimes' => 'O comprovante deve ser JPG, PNG ou PDF.',
            'receipt.max' => 'O comprovante não pode ter mais de 5MB.',
        ];
    }
}
