<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use App\Traits\FlushesUserCache;
use App\Traits\HasUuid;
use App\Traits\LogsOwnedActivity;
use Carbon\Carbon;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Orçamento (teto de gastos) de um mês.
 * category_id NULL = orçamento geral do mês (todas as despesas).
 *
 * Os atributos `spent`, `remaining` e `percent` NÃO são colunas: são
 * preenchidos pelo BudgetService ao calcular o consumo do período.
 */
class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use BelongsToUser, FlushesUserCache, HasFactory, HasUuid, LogsOwnedActivity, SoftDeletes;

    /**
     * Attributes recorded in the activity log (`notes` is never logged).
     *
     * @return list<string>
     */
    protected function activityLogAttributes(): array
    {
        return ['category_id', 'year', 'month', 'amount', 'alert_threshold'];
    }

    protected $fillable = [
        'user_id',
        'category_id',
        'year',
        'month',
        'amount',
        'alert_threshold',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'year' => 'integer',
            'month' => 'integer',
            'alert_threshold' => 'integer',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function isGeneral(): bool
    {
        return is_null($this->category_id);
    }

    public function getLabelAttribute(): string
    {
        return $this->isGeneral() ? 'Orçamento geral' : ($this->category->name ?? 'Categoria removida');
    }

    public function getPeriodLabelAttribute(): string
    {
        return ucfirst(Carbon::create($this->year, $this->month, 1)->locale('pt_BR')->translatedFormat('F/Y'));
    }

    /**
     * Situação do consumo: ok | warning | exceeded.
     */
    public function getHealthAttribute(): string
    {
        $percent = (float) ($this->percent ?? 0);

        return match (true) {
            $percent >= 100 => 'exceeded',
            $percent >= $this->alert_threshold => 'warning',
            default => 'ok',
        };
    }
}
