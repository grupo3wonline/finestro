<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ReportFilterData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Report filters shared by the screen, print, PDF, e-mail and export actions.
 */
class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Access is gated by the route middleware (permission:reports.*) and
        // the plan checks in ReportController.
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'type' => ['nullable', 'in:income,expense'],
            'status' => ['nullable', 'in:paid,pending'],
            'format' => ['nullable', 'in:csv,xlsx'],
            // Accepts "wallet" (form field name) and "wallet_id".
            'wallet_id' => ['nullable', 'string', 'max:64'],
            'wallet' => ['nullable', 'string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'format.in' => 'Formato de exportação inválido.',
        ];
    }

    public function filters(): ReportFilterData
    {
        $validated = $this->validated();
        $wallet = $validated['wallet_id'] ?? $validated['wallet'] ?? null;

        return ReportFilterData::fromValidated($validated, $wallet);
    }
}
