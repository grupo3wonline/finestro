<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Filters of the transaction list, shared by the web screen and the API.
 */
class TransactionIndexRequest extends FormRequest
{
    public const int DEFAULT_PER_PAGE = 20;

    /** Filters passed through to the repository as they arrive. */
    private const array FILTER_KEYS = ['type', 'wallet_id', 'category_id', 'status', 'payment_method', 'search'];

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Transaction::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'month' => ['nullable', 'regex:/^(all|[1-9]|1[0-2])$/'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:income,expense'],
            'status' => ['nullable', 'in:paid,pending,overdue'],
            'wallet_id' => ['nullable', 'uuid'],
            'category_id' => ['nullable', 'uuid'],
            'payment_method' => ['nullable', Rule::in(PaymentMethod::values())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('api.per_page_max')],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'month.regex' => 'Mês inválido. Use 1 a 12 ou "all".',
            'year.integer' => 'Ano inválido.',
            'year.min' => 'Ano inválido.',
            'year.max' => 'Ano inválido.',
            'search.max' => 'A busca pode ter no máximo 100 caracteres.',
            'type.in' => 'Tipo de transação inválido.',
            'status.in' => 'Situação inválida.',
            'wallet_id.uuid' => 'Carteira inválida.',
            'category_id.uuid' => 'Categoria inválida.',
            'payment_method.in' => 'Forma de pagamento inválida.',
            'per_page.integer' => 'O parâmetro per_page deve ser um número inteiro.',
            'per_page.min' => 'O parâmetro per_page deve ser no mínimo 1.',
            'per_page.max' => 'O parâmetro per_page deve ser no máximo '.(int) config('api.per_page_max').'.',
            'page.integer' => 'O parâmetro page deve ser um número inteiro.',
            'page.min' => 'O parâmetro page deve ser no mínimo 1.',
        ];
    }

    public function year(): int
    {
        return (int) ($this->validated('year') ?? now()->year);
    }

    /** Month number as a string ("1".."12") or "all" for the whole year. */
    public function month(): string
    {
        return (string) ($this->validated('month') ?? now()->month);
    }

    /**
     * Repository filters: the list filters plus the period.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_merge(
            Arr::only($this->validated(), self::FILTER_KEYS),
            ['year' => $this->year(), 'month' => $this->month()]
        );
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? self::DEFAULT_PER_PAGE);
    }
}
