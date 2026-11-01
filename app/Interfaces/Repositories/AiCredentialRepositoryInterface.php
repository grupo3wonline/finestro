<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\DTOs\AiCredentialDTO;
use App\Models\AiCredential;

interface AiCredentialRepositoryInterface
{
    /**
     * Create or update the single AI credential row of the given user.
     */
    public function updateOrCreateForUser(string $userId, AiCredentialDTO $dto): AiCredential;
}
