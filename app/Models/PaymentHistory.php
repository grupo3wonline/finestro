<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\PaymentHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The column is a NOT NULL enum (default 'pending'), but an unsaved instance
 * has no status yet, so getStatusLabelAttribute() keeps its fallback arm.
 *
 * @property string|null $status
 */
class PaymentHistory extends Model
{
    /** @use HasFactory<PaymentHistoryFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $table = 'payment_histories';

    protected $fillable = [
        'user_id',
        'subscription_id',
        'stripe_invoice_id',
        'stripe_payment_intent_id',
        'amount',
        'currency',
        'status',
        'description',
        'paid_at',
        'hosted_invoice_url',
        'invoice_pdf_url',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Link to the Stripe-hosted invoice/receipt, when available.
     */
    public function getReceiptUrlAttribute(): ?string
    {
        return $this->hosted_invoice_url ?? $this->invoice_pdf_url;
    }

    /**
     * Status label in pt-BR for the UI.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'Pago',
            'failed' => 'Falhou',
            'pending' => 'Pendente',
            'refunded' => 'Reembolsado',
            default => (string) $this->status,
        };
    }
}
