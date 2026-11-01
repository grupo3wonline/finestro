<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pagination parameters shared by the API list endpoints.
 */
class ListRequest extends FormRequest
{
    public const int DEFAULT_PER_PAGE = 20;

    public function authorize(): bool
    {
        // viewAny is authorized in the controller.
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('api.per_page_max')],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'per_page.integer' => 'O parâmetro per_page deve ser um número inteiro.',
            'per_page.min' => 'O parâmetro per_page deve ser no mínimo 1.',
            'per_page.max' => 'O parâmetro per_page deve ser no máximo '.(int) config('api.per_page_max').'.',
            'page.integer' => 'O parâmetro page deve ser um número inteiro.',
            'page.min' => 'O parâmetro page deve ser no mínimo 1.',
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? self::DEFAULT_PER_PAGE);
    }
}
