<?php

namespace App\Interfaces\Services;

use App\DTOs\WalletDTO;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;

interface WalletServiceInterface
{
    public function getAllWallets(array $filters = []): Collection;

    public function getWalletById(string $id): ?Wallet;

    public function createWallet(WalletDTO $dto): Wallet;

    public function updateWallet(string $id, WalletDTO $dto): Wallet;

    public function deleteWallet(string $id): bool;
}
