<?php

namespace App\Interfaces\Repositories;

use App\DTOs\WalletDTO;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;

interface WalletRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function findById(string $id): ?Wallet;

    public function create(WalletDTO $dto): Wallet;

    public function update(string $id, WalletDTO $dto): Wallet;

    public function delete(string $id): bool;
}
