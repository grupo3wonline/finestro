<?php

namespace App\Repositories;

use App\DTOs\AiCredentialDTO;
use App\Interfaces\Repositories\AiCredentialRepositoryInterface;
use App\Models\AiCredential;

class AiCredentialRepository implements AiCredentialRepositoryInterface
{
    public function __construct(
        protected AiCredential $model
    ) {}

    public function updateOrCreateForUser(string $userId, AiCredentialDTO $dto): AiCredential
    {
        return $this->model->newQuery()->updateOrCreate(
            ['user_id' => $userId],
            $dto->toArray(),
        );
    }
}
