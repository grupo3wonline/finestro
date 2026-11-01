<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\FaqCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Categoria (grupo) de perguntas da Central de Ajuda.
 * Conteúdo público, não escopado por usuário.
 */
class FaqCategory extends Model
{
    /** @use HasFactory<FaqCategoryFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<FaqItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(FaqItem::class);
    }

    /** @return HasMany<FaqItem, $this> */
    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
