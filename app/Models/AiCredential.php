<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiCredential extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'user_id',
        'gemini_api_key',
        'groq_api_key',
        'openrouter_api_key',
        'preferred_provider',
    ];

    protected function casts(): array
    {
        return [
            'gemini_api_key' => 'encrypted',
            'groq_api_key' => 'encrypted',
            'openrouter_api_key' => 'encrypted',
        ];
    }

    protected $hidden = [
        'gemini_api_key',
        'groq_api_key',
        'openrouter_api_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the user has configured the preferred provider.
     */
    public function hasConfiguredProvider(): bool
    {
        return match ($this->preferred_provider) {
            'gemini' => !empty($this->gemini_api_key),
            'groq' => !empty($this->groq_api_key),
            'openrouter' => !empty($this->openrouter_api_key),
            default => false,
        };
    }

    /**
     * Get the API key for the preferred provider.
     */
    public function getActiveApiKey(): ?string
    {
        return match ($this->preferred_provider) {
            'gemini' => $this->gemini_api_key,
            'groq' => $this->groq_api_key,
            'openrouter' => $this->openrouter_api_key,
            default => null,
        };
    }
}
