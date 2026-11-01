<?php

namespace App\Http\Requests;

use App\Models\Budget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Updates are authorized in BudgetController after the scoped lookup
        // (ownership -> 404, permission -> 403).
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return true;
        }

        return $this->user()?->can('create', Budget::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');

        if (is_string($amount) && str_contains($amount, ',')) {
            $this->merge(['amount' => str_replace(',', '.', str_replace('.', '', $amount))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            // Na edição, categoria e período não mudam (edite o valor ou exclua).
            'category_id' => $isUpdate ? ['prohibited'] : [
                'nullable', 'uuid',
                Rule::exists('categories', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('type', 'expense')
                    ->whereNull('deleted_at'),
            ],
            'year' => $isUpdate ? ['prohibited'] : ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => $isUpdate ? ['prohibited'] : ['required', 'integer', 'min:1', 'max:12'],
            'amount' => ['required', 'numeric', 'min:1', 'max:9999999999999'],
            'alert_threshold' => ['nullable', 'integer', 'min:10', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
            'repeat_until_december' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'Selecione uma categoria de despesa válida.',
            'amount.required' => 'Informe o valor do orçamento.',
            'amount.min' => 'O orçamento deve ser de pelo menos R$ 1,00.',
            'alert_threshold.min' => 'O alerta deve ser de no mínimo 10%.',
            'alert_threshold.max' => 'O alerta deve ser de no máximo 100%.',
        ];
    }
}
