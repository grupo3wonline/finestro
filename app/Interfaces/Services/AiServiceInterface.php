<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\DTOs\AiCredentialDTO;
use App\Models\AiCredential;
use App\Models\User;

interface AiServiceInterface
{
    /**
     * Generate an AI response for the given user, prompt and optional context.
     *
     * @return array{success: bool, content?: string, provider?: string, error?: string}
     */
    public function generate(User $user, string $prompt, string $context = ''): array;

    /**
     * Build a textual financial context (balances, income, goals, top categories)
     * from the user's data to ground the AI responses.
     */
    public function buildFinancialContext(User $user): string;

    /**
     * Persist the AI provider credentials of the given user.
     */
    public function saveCredentials(User $user, AiCredentialDTO $dto): AiCredential;
}
