<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class WalletDTO extends Data
{
    /**
     * Optional columns an update may clear. StoreWalletRequest and
     * UpdateWalletRequest accept null for each of them on every wallet type
     * (a card without closing/due day simply has no invoice cycle yet).
     */
    public const array CLEARABLE_ON_UPDATE = ['company_name', 'company_document', 'credit_limit', 'closing_day', 'due_day'];

    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $userId = null,
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly ?string $ownership = null,
        public readonly ?string $companyName = null,
        public readonly ?string $companyDocument = null,
        public readonly ?float $balance = null,
        public readonly ?float $creditLimit = null,
        public readonly ?int $closingDay = null,
        public readonly ?int $dueDay = null,
        public readonly ?string $currency = null,
        public readonly ?string $color = null,
        public readonly ?string $icon = null,
        public readonly ?string $description = null,
        public readonly ?bool $isActive = null,
        public readonly ?bool $isShared = null,
        /** @var list<string> Columns an update sets to null on purpose. */
        public readonly array $cleared = [],
    ) {}

    /**
     * Create DTO from request array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::build($data, []);
    }

    /**
     * Create an update DTO from validated request data: a clearable field
     * that is present and null (blank form input or JSON null) is cleared;
     * an omitted field keeps its current value.
     *
     * @param  array<string, mixed>  $data
     */
    public static function forUpdate(array $data): self
    {
        $cleared = array_values(array_filter(
            self::CLEARABLE_ON_UPDATE,
            fn (string $key): bool => array_key_exists($key, $data) && $data[$key] === null,
        ));

        return self::build($data, $cleared);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $cleared
     */
    private static function build(array $data, array $cleared): self
    {
        return new self(
            id: $data['id'] ?? null,
            userId: $data['user_id'] ?? null,
            name: $data['name'] ?? null,
            type: $data['type'] ?? null,
            ownership: $data['ownership'] ?? null,
            companyName: $data['company_name'] ?? null,
            companyDocument: $data['company_document'] ?? null,
            balance: isset($data['balance']) ? (float) $data['balance'] : null,
            creditLimit: isset($data['credit_limit']) ? (float) $data['credit_limit'] : null,
            closingDay: isset($data['closing_day']) ? (int) $data['closing_day'] : null,
            dueDay: isset($data['due_day']) ? (int) $data['due_day'] : null,
            currency: $data['currency'] ?? null,
            color: $data['color'] ?? null,
            icon: $data['icon'] ?? null,
            description: $data['description'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            isShared: isset($data['is_shared']) ? (bool) $data['is_shared'] : null,
            cleared: $cleared,
        );
    }

    /**
     * Convert DTO to array for model operations. Null values are dropped
     * (unchanged on update) except the explicitly cleared columns.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->presentValues() + array_fill_keys($this->cleared, null);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentValues(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'name' => $this->name,
            'type' => $this->type,
            'ownership' => $this->ownership,
            'company_name' => $this->companyName,
            'company_document' => $this->companyDocument,
            'balance' => $this->balance,
            'credit_limit' => $this->creditLimit,
            'closing_day' => $this->closingDay,
            'due_day' => $this->dueDay,
            'currency' => $this->currency,
            'color' => $this->color,
            'icon' => $this->icon,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'is_shared' => $this->isShared,
        ], fn (float|int|string|bool|null $value): bool => ! is_null($value));
    }
}
