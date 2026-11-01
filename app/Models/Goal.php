<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToUser;
use App\Traits\Filterable;
use App\Traits\FlushesUserCache;
use App\Traits\HasUuid;
use App\Traits\LogsOwnedActivity;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use BelongsToUser, Filterable, FlushesUserCache, HasFactory, HasUuid, LogsOwnedActivity, SoftDeletes;

    /**
     * Attributes recorded in the activity log.
     *
     * @return list<string>
     */
    protected function activityLogAttributes(): array
    {
        return ['name', 'description', 'target_amount', 'current_amount', 'deadline', 'status'];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'target_amount',
        'current_amount',
        'deadline',
        'status',
        'icon',
        'color',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'deadline' => 'date',
        ];
    }

    /**
     * Filterable fields.
     *
     * @var array<int, string>
     */
    protected array $filterable = ['status'];

    /**
     * Get the progress percentage.
     */
    public function getProgressAttribute(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        return min(100, round(($this->current_amount / $this->target_amount) * 100, 2));
    }
}
