<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletMember extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'wallet_id',
        'user_id',
        'role',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Get the wallet this membership belongs to.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the user this membership belongs to.
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
}
