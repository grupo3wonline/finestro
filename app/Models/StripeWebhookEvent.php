<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\StripeWebhookEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Marker of a Stripe webhook event already processed (idempotency).
 */
class StripeWebhookEvent extends Model
{
    /** @use HasFactory<StripeWebhookEventFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'stripe_event_id',
        'type',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
