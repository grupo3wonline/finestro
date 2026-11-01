<?php

namespace App\DTOs;

use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;

class AiCredentialDTO extends Data
{
    /**
     * The API keys are #[Hidden] so transform()/toJson() never expose them;
     * toArray() below is overridden for persistence and still includes them.
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $preferredProvider,
        #[Hidden]
        public readonly ?string $geminiApiKey = null,
        #[Hidden]
        public readonly ?string $groqApiKey = null,
        #[Hidden]
        public readonly ?string $openrouterApiKey = null,
    ) {}

    /**
     * Create DTO from validated request data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            preferredProvider: $data['preferred_provider'],
            geminiApiKey: $data['gemini_api_key'] ?? null,
            groqApiKey: $data['groq_api_key'] ?? null,
            openrouterApiKey: $data['openrouter_api_key'] ?? null,
        );
    }

    /**
     * Convert DTO to array for model persistence.
     *
     * Nulls are kept on purpose: submitting an empty key clears it.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'gemini_api_key' => $this->geminiApiKey,
            'groq_api_key' => $this->groqApiKey,
            'openrouter_api_key' => $this->openrouterApiKey,
            'preferred_provider' => $this->preferredProvider,
        ];
    }
}
