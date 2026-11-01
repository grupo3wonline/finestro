<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use App\Traits\HasUuid;
use App\Traits\LogsOwnedActivity;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use BelongsToUser, HasFactory, HasUuid, LogsOwnedActivity, SoftDeletes;

    /**
     * Attributes recorded in the activity log.
     *
     * @return list<string>
     */
    protected function activityLogAttributes(): array
    {
        return ['name', 'type', 'icon', 'color'];
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
        'icon',
        'color',
    ];

    /**
     * Get the transactions for this category.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
