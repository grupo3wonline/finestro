<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the credit-card invoice payment form.
 * Authorization is handled by the WalletPolicy called in WalletController.
 */
class PayInvoiceRequest extends FormRequest
{
    /**
     * Authorization is delegated to the WalletPolicy (checked in the controller
     * before this FormRequest is resolved).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // The route parameter is the credit-card wallet id being paid; the
        // source wallet must be a *different* wallet owned by the same user.
        // The route uses the {wallet} placeholder (not {id}), so we resolve it
        // by name rather than relying on positional controller binding.
        $walletId = (string) ($this->route('wallet') ?? $this->route('id') ?? '');
        $userId = (string) $this->user()->id;

        return [
            'source_wallet_id' => [
                'required',
                'uuid',
                Rule::exists('wallets', 'id')
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at'),
                Rule::notIn([$walletId]),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'ref' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source_wallet_id.required' => 'Selecione a conta de onde sai o pagamento.',
            'source_wallet_id.exists' => 'Conta de origem inválida.',
            'date.before_or_equal' => 'A data de pagamento não pode ser futura.',
        ];
    }
}
