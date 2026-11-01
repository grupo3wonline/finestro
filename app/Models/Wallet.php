<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use App\Traits\Filterable;
use App\Traits\FlushesUserCache;
use App\Traits\HasUuid;
use App\Traits\LogsOwnedActivity;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use BelongsToUser, Filterable, FlushesUserCache, HasFactory, HasUuid, LogsOwnedActivity, SoftDeletes;

    /**
     * Attributes recorded in the activity log. `balance` (covered by the
     * transaction rows) and `company_document` are never logged.
     *
     * @return list<string>
     */
    protected function activityLogAttributes(): array
    {
        return [
            'name', 'type', 'ownership', 'company_name', 'credit_limit', 'closing_day', 'due_day',
            'currency', 'color', 'icon', 'description', 'is_active', 'is_shared',
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
            'closing_day' => 'integer',
            'due_day' => 'integer',
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
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the members (shared users) for this wallet.
     *
     * @return HasMany<WalletMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(WalletMember::class);
    }

    /**
     * Get the users this wallet is shared with (via pivot).
     *
     * @return BelongsToMany<User, $this>
     */
    public function sharedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wallet_members')
            ->withPivot('role', 'accepted_at')
            ->withTimestamps();
    }

    /**
     * Check if the given user owns this wallet.
     */
    public function isOwnedBy(User $user): bool
    {
        return (string) $this->user_id === (string) $user->id;
    }

    /**
     * Check if the given user is an accepted (non-pending) member of this wallet.
     */
    public function hasAcceptedMember(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->whereNotNull('accepted_at')
            ->exists();
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
     * Valor utilizado do limite do cartão.
     *
     * Convenção do Finestro: despesas SUBTRAEM do saldo da carteira. Num cartão
     * de crédito isso significa que saldo negativo = dívida (fatura em aberto)
     * e um pagamento de fatura (receita na carteira do cartão) traz o saldo
     * de volta a zero. Antes desta correção o percentual de uso ficava
     * negativo e o crédito disponível superava o limite.
     */
    public function getCreditUsedAttribute(): ?float
    {
        if (! $this->isCreditCard()) {
            return null;
        }

        return max(0.0, -1 * (float) $this->balance);
    }

    /**
     * Get available credit for credit card wallets.
     */
    public function getAvailableCreditAttribute(): ?float
    {
        if (! $this->isCreditCard() || ! $this->credit_limit) {
            return null;
        }

        return (float) $this->credit_limit - (float) $this->credit_used;
    }

    /**
     * Get the credit usage percentage.
     */
    public function getCreditUsagePercentAttribute(): ?float
    {
        if (! $this->isCreditCard() || ! $this->credit_limit || (float) $this->credit_limit === 0.0) {
            return null;
        }

        return ((float) $this->credit_used / (float) $this->credit_limit) * 100;
    }

    /**
     * Indica se o cartão tem dados suficientes para calcular fatura.
     */
    public function hasInvoiceCycle(): bool
    {
        return $this->isCreditCard() && $this->closing_day && $this->due_day;
    }

    /**
     * Ciclo de fatura que contém a data informada (padrão: hoje).
     *
     * Exemplo: fechamento dia 5, vencimento dia 12.
     *  - Compra em 03/03 → fatura que fecha em 05/03 e vence em 12/03.
     *  - Compra em 06/03 → fatura que fecha em 05/04 e vence em 12/04.
     *
     * Dias maiores que o último dia do mês (ex.: 31 em fevereiro) são
     * ajustados para o último dia do mês.
     *
     * @return array{start: Carbon, end: Carbon, due: Carbon}|null
     */
    public function invoiceCycleFor(?CarbonInterface $date = null): ?array
    {
        if (! $this->hasInvoiceCycle()) {
            return null;
        }

        $date = Carbon::parse($date ?? Carbon::today())->startOfDay();

        $closingThisMonth = $this->dayInMonth($date->year, $date->month, $this->closing_day);
        $end = $date->lte($closingThisMonth)
            ? $closingThisMonth
            : $this->dayInMonth($date->copy()->addMonthNoOverflow()->year, $date->copy()->addMonthNoOverflow()->month, $this->closing_day);

        $prevMonth = $end->copy()->subMonthNoOverflow();
        $start = $this->dayInMonth($prevMonth->year, $prevMonth->month, $this->closing_day)->addDay();

        // Vencimento: no mesmo mês do fechamento se o dia de vencimento for depois
        // do fechamento; caso contrário, no mês seguinte.
        $due = $this->due_day > $this->closing_day
            ? $this->dayInMonth($end->year, $end->month, $this->due_day)
            : $this->dayInMonth($end->copy()->addMonthNoOverflow()->year, $end->copy()->addMonthNoOverflow()->month, $this->due_day);

        return ['start' => $start, 'end' => $end, 'due' => $due];
    }

    private function dayInMonth(int $year, int $month, int $day): Carbon
    {
        $first = Carbon::create($year, $month, 1)->startOfDay();

        return $first->day(min($day, $first->daysInMonth));
    }
}
