<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'in:checking,savings,credit,investment,cash'],
            'ownership' => ['sometimes', 'required', 'string', 'in:personal,business'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_document' => ['nullable', 'string', 'max:20'],
            'balance' => ['nullable', 'numeric'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'closing_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'currency' => ['nullable', 'string', 'size:3'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da carteira é obrigatório.',
            'type.in' => 'Tipo de carteira inválido.',
            'balance.numeric' => 'O saldo deve ser um valor numérico.',
            'company_name.max' => 'O nome da empresa não pode ter mais de 255 caracteres.',
            'company_document.max' => 'O CNPJ/documento não pode ter mais de 20 caracteres.',
            'credit_limit.numeric' => 'O limite do cartão deve ser um valor numérico.',
            'credit_limit.min' => 'O limite do cartão não pode ser negativo.',
            'closing_day.integer' => 'O dia de fechamento deve ser um número inteiro.',
            'closing_day.min' => 'O dia de fechamento deve estar entre 1 e 31.',
            'closing_day.max' => 'O dia de fechamento deve estar entre 1 e 31.',
            'due_day.integer' => 'O dia de vencimento deve ser um número inteiro.',
            'due_day.min' => 'O dia de vencimento deve estar entre 1 e 31.',
            'due_day.max' => 'O dia de vencimento deve estar entre 1 e 31.',
        ];
    }
}
