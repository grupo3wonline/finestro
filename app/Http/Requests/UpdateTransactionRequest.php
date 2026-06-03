<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['sometimes', 'required', 'uuid', 'exists:wallets,id'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'type' => ['sometimes', 'required', 'string', 'in:income,expense'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'date' => ['sometimes', 'required', 'date'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurring_frequency' => ['nullable', 'string', 'in:daily,weekly,monthly,yearly'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.exists' => 'Carteira não encontrada.',
            'type.in' => 'Tipo de transação inválido.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'date.date' => 'Data inválida.',
        ];
    }
}
