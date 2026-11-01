<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ApiTokenAbility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Plan gating happens in ApiTokenService (redirect with a pt-BR error).
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['required', 'string', 'distinct', Rule::in(ApiTokenAbility::values())],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Dê um nome ao token (ex.: "Planilha Google").',
            'name.max' => 'O nome do token deve ter no máximo 60 caracteres.',
            'abilities.required' => 'Selecione pelo menos uma permissão para o token.',
            'abilities.min' => 'Selecione pelo menos uma permissão para o token.',
            'abilities.*.in' => 'Uma das permissões selecionadas é inválida.',
            'abilities.*.distinct' => 'Há permissões repetidas na seleção.',
        ];
    }
}
