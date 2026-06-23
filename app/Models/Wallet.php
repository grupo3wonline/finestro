<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use App\Traits\Filterable;
use App\Traits\HasUuid;
use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use BelongsToUser, Filterable, HasFactory, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'ownership',
        'company_name',
        'company_document',
        'balance',
        'credit_limit',
        'closing_day',
        'due_day',
        'currency',
        'color',
        'icon',
        'description',
        'is_active',
        'is_shared',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'closing_day' => 'date',
            'due_day' => 'date',
            'is_active' => 'boolean',
            'is_shared' => 'boolean',
        ];
    }

    /**
     * Filterable fields.
     *
     * @var array<int, string>
     */
    protected array $filterable = ['type', 'ownership', 'is_active', 'currency'];

    /**
     * Get the transactions for this wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the members (shared users) for this wallet.
     */
    public function members(): HasMany
    {
        return $this->hasMany(WalletMember::class);
    }

    /**
     * Get the users this wallet is shared with (via pivot).
     */
    public function sharedUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wallet_members')
            ->withPivot('role', 'accepted_at')
            ->withTimestamps();
    }

    /**
     * Check if wallet is a business wallet.
     */
    public function isBusiness(): bool
    {
        return $this->ownership === 'business';
    }

    /**
     * Check if wallet is a credit card type.
     */
    public function isCreditCard(): bool
    {
        return $this->type === 'credit';
    }

    /**
     * Get available credit for credit card wallets.
     */
    public function getAvailableCreditAttribute(): ?float
    {
        if (!$this->isCreditCard() || !$this->credit_limit) {
            return null;
        }

        return (float) $this->credit_limit - (float) $this->balance;
    }

    /**
     * Get the credit usage percentage.
     */
    public function getCreditUsagePercentAttribute(): ?float
    {
        if (!$this->isCreditCard() || !$this->credit_limit || $this->credit_limit == 0) {
            return null;
        }

        return ((float) $this->balance / (float) $this->credit_limit) * 100;
    }
}
