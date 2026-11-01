<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\WalletMemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletMember extends Model
{
    /** @use HasFactory<WalletMemberFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'wallet_id',
        'user_id',
        'email',
        'role',
        'invitation_token',
        'accepted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the wallet this membership belongs to.
     *
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the user this membership belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the member can edit the wallet.
     */
    public function canEdit(): bool
    {
        return in_array($this->role, ['editor', 'admin']);
    }

    /**
     * Check if the member can manage (add/remove members).
     */
    public function canManage(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the invitation has been accepted.
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * A pending invite for someone who has not registered/claimed it yet.
     */
    public function isUnclaimed(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Whether the (token-based) invitation has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Pending invitations that still can be claimed for the given email.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeClaimableForEmail(Builder $query, string $email): Builder
    {
        return $query->whereNull('user_id')
            ->where('email', mb_strtolower(trim($email)))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
