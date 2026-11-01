<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\CookieConsent;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Cookie preferences sent by the banner (JSON) or the privacy forms.
 * Open to guests: consent applies to every visitor.
 */
class StoreConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'analytics' => ['required', 'boolean'],
            'marketing' => ['required', 'boolean'],
            'source' => ['required', 'string', 'in:'.CookieConsent::SOURCE_BANNER.','.CookieConsent::SOURCE_SETTINGS],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'analytics.required' => 'Informe se aceita cookies de análise.',
            'analytics.boolean' => 'Valor inválido para cookies de análise.',
            'marketing.required' => 'Informe se aceita cookies de marketing.',
            'marketing.boolean' => 'Valor inválido para cookies de marketing.',
            'source.in' => 'Origem da preferência inválida.',
        ];
    }
}
