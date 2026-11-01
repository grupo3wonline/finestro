<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Traits\BelongsToUser;
use App\Traits\Filterable;
use App\Traits\FlushesUserCache;
use App\Traits\HasUuid;
use App\Traits\LogsOwnedActivity;
use Carbon\Carbon;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Builder as ScoutBuilder;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Transaction extends Model implements HasMedia
{
    /** Origem: pagamento de fatura de cartão (gerado por CreditCardInvoiceService). */
    public const ORIGIN_INVOICE_PAYMENT = 'invoice_payment';

    /** Origem: ocorrência gerada automaticamente por uma recorrência. */
    public const ORIGIN_RECURRING = 'recurring';

    /** @use HasFactory<TransactionFactory> */
    use BelongsToUser, Filterable, FlushesUserCache, HasFactory, HasUuid, InteractsWithMedia, LogsOwnedActivity, Searchable, SoftDeletes;

    /** Media collection that holds the (single) receipt file. */
    public const RECEIPT_COLLECTION = 'receipt';

    /**
     * Receipts: one file per transaction on the private disk. Adding a new
     * file replaces the previous one. Media Library sniffs the real content
     * type, so a renamed file is rejected even if its extension looks valid.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::RECEIPT_COLLECTION)
            ->singleFile()
            ->useDisk('private')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }

    /**
     * Attributes recorded in the activity log. `notes` (free text) and
     * `receipt_path` are never logged.
     *
     * @return list<string>
     */
    protected function activityLogAttributes(): array
    {
        return [
            'wallet_id', 'category_id', 'type', 'status', 'amount', 'description', 'date', 'due_date',
            'paid_at', 'payment_method', 'is_recurring', 'recurring_frequency', 'installment_number',
            'installment_total',
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'wallet_id',
        'category_id',
        'type',
        'status',
        'amount',
        'description',
        'date',
        'due_date',
        'paid_at',
        'payment_method',
        'is_recurring',
        'recurring_frequency',
        'recurring_source_id',
        'installment_group_id',
        'installment_number',
        'installment_total',
        'origin',
        'receipt_path',
        'notes',
    ];

    /**
     * Default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'paid',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'is_recurring' => 'boolean',
            'installment_number' => 'integer',
            'installment_total' => 'integer',
        ];
    }

    /**
     * Filterable fields.
     *
     * @var array<int, string>
     */
    protected array $filterable = ['type', 'wallet_id', 'category_id', 'is_recurring', 'status', 'payment_method'];

    /**
     * Invariante: transação paga sempre tem `paid_at`; pendente nunca tem.
     * Garantido aqui (e não só no Service) para valer em qualquer caminho de
     * gravação: seeders, comandos agendados, tinker, etc.
     */
    protected static function booted(): void
    {
        static::saving(function (Transaction $transaction): void {
            if ($transaction->status === TransactionStatus::PENDING->value) {
                $transaction->paid_at = null;
            } elseif (is_null($transaction->paid_at)) {
                $transaction->paid_at = now();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** @return BelongsTo<Wallet, $this> */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<TransactionHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(TransactionHistory::class)->latest();
    }

    /**
     * Demais parcelas da mesma compra (inclui a própria).
     *
     * @return HasMany<self, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(self::class, 'installment_group_id', 'installment_group_id')
            ->orderBy('installment_number');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::PAID->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::PENDING->value);
    }

    /**
     * Pendentes com vencimento anterior a hoje.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->pending()
            ->whereRaw('COALESCE(due_date, date) < ?', [Carbon::today()->toDateString()]);
    }

    /**
     * Filtra por competência (mês/ano da data da transação).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInMonth(Builder $query, int $year, int $month): Builder
    {
        // A half-open range [first day, first day of next month) is portable
        // across MySQL and SQLite and index-friendly. It also sidesteps
        // whereMonth's driver differences (SQLite's strftime('%m') returns a
        // zero-padded "08" that never equals the unpadded "8" Laravel binds)
        // without dropping the last day of the month to a time component.
        $start = Carbon::create($year, $month, 1)->startOfDay();

        return $query
            ->where('date', '>=', $start->toDateString())
            ->where('date', '<', $start->copy()->addMonth()->toDateString());
    }

    /*
    |--------------------------------------------------------------------------
    | Search (Laravel Scout, database engine)
    |--------------------------------------------------------------------------
    */

    /**
     * Columns searched by the Scout database engine. Dotted keys point at the
     * joined tables from newScoutQuery() and are used as-is by the engine.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'description' => $this->description,
            'notes' => $this->notes,
            'categories.name' => $this->category?->name,
            'wallets.name' => $this->wallet?->name,
        ];
    }

    /**
     * Base query for Scout searches. Starts from newQuery(), so the `user`
     * global scope and SoftDeletes still apply.
     *
     * @param  ScoutBuilder<self>  $builder
     * @return Builder<static>
     */
    public function newScoutQuery(ScoutBuilder $builder): Builder
    {
        return $this->newQuery()
            ->select('transactions.*')
            ->leftJoin('categories', 'categories.id', '=', 'transactions.category_id')
            ->leftJoin('wallets', 'wallets.id', '=', 'transactions.wallet_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isIncome(): bool
    {
        return $this->type === 'income';
    }

    public function isPaid(): bool
    {
        return $this->status === TransactionStatus::PAID->value;
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::PENDING->value;
    }

    /**
     * A receipt is either a media item or a legacy `receipt_path` file
     * (rows created before the Media Library migration).
     */
    public function hasReceipt(): bool
    {
        return $this->getFirstMedia(self::RECEIPT_COLLECTION) instanceof Media || filled($this->receipt_path);
    }

    public function isInstallment(): bool
    {
        return ! is_null($this->installment_group_id) && (int) $this->installment_total > 1;
    }

    /**
     * Data efetiva de vencimento (se não informada, usa a data da transação).
     */
    public function getEffectiveDueDateAttribute(): ?Carbon
    {
        return $this->due_date ?? $this->date;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->isPending()
            && $this->effective_due_date
            && $this->effective_due_date->lt(Carbon::today());
    }

    public function getStatusLabelAttribute(): string
    {
        $status = TransactionStatus::tryFrom((string) $this->status) ?? TransactionStatus::PAID;

        if ($this->is_overdue) {
            return 'Atrasado';
        }

        return $status->label($this->type);
    }

    public function getPaymentMethodLabelAttribute(): ?string
    {
        return PaymentMethod::tryFrom((string) $this->payment_method)?->label();
    }

    public function getInstallmentLabelAttribute(): ?string
    {
        return $this->isInstallment()
            ? "{$this->installment_number}/{$this->installment_total}"
            : null;
    }

    /**
     * Valor com sinal (+ receita / - despesa) — útil para somatórios.
     */
    public function getSignedAmountAttribute(): float
    {
        return $this->isIncome() ? (float) $this->amount : -1 * (float) $this->amount;
    }
}
