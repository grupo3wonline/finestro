<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToUser
{
    /**
     * Boot the BelongsToUser trait.
     * Automatically scopes queries to the authenticated user
     * and assigns user_id on creation.
     */
    protected static function bootBelongsToUser(): void
    {
        static::creating(function ($model): void {
            if (Auth::check() && empty($model->user_id)) {
                $model->user_id = Auth::id();
            }
        });

        static::addGlobalScope('user', function (Builder $builder): void {
            if (Auth::check()) {
                $builder->where($builder->getModel()->getTable().'.user_id', Auth::id());
            }
        });
    }

    /**
     * Get the user that owns this record.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
