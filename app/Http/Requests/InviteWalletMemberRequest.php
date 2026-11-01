<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteWalletMemberRequest extends FormRequest
{
    /**
     * Ownership and plan gating are enforced in the controller via
     * WalletPolicy::share (authorize('share', $wallet)).
     */
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', 'string', 'in:viewer,editor,admin'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Informe o e-mail da pessoa que você quer convidar.',
            'email.email' => 'Informe um e-mail válido.',
            'role.required' => 'Selecione o nível de acesso.',
            'role.in' => 'Nível de acesso inválido.',
        ];
    }
}
