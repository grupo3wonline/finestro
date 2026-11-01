<?php

namespace Database\Factories;

use App\Models\AiCredential;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiCredential>
 */
class AiCredentialFactory extends Factory
{
    protected $model = AiCredential::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gemini_api_key' => 'test-gemini-'.fake()->sha1(),
            'groq_api_key' => null,
            'openrouter_api_key' => null,
            'preferred_provider' => 'gemini',
        ];
    }

    /**
     * Credential configured to use Groq.
     */
    public function groq(): static
    {
        return $this->state(fn (): array => [
            'groq_api_key' => 'test-groq-'.fake()->sha1(),
            'preferred_provider' => 'groq',
        ]);
    }

    /**
     * Credential configured to use OpenRouter.
     */
    public function openRouter(): static
    {
        return $this->state(fn (): array => [
            'openrouter_api_key' => 'test-openrouter-'.fake()->sha1(),
            'preferred_provider' => 'openrouter',
        ]);
    }
}
