<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TransactionRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    use TransactionRules;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge($this->sharedRules(), [
            'wallet_id' => ['sometimes', 'required', 'uuid', $this->ownedWalletRule()],
            'type' => ['sometimes', 'required', 'string', 'in:income,expense'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:9999999999999'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'date' => ['sometimes', 'required', 'date'],
        ]);
    }

    public function messages(): array
    {
        return $this->sharedMessages();
    }
}
