<?php

namespace App\Services;

use App\DTOs\WalletDTO;
use App\Interfaces\Repositories\WalletRepositoryInterface;
use App\Interfaces\Services\WalletServiceInterface;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;

class WalletService implements WalletServiceInterface
{
    public function __construct(
        protected WalletRepositoryInterface $walletRepository
    ) {}

    public function getAllWallets(array $filters = []): Collection
    {
        return $this->walletRepository->getAll($filters);
    }

    public function getWalletById(string $id): ?Wallet
    {
        return $this->walletRepository->findById($id);
    }

    public function createWallet(WalletDTO $dto): Wallet
    {
        return $this->walletRepository->create($dto);
    }

    public function updateWallet(string $id, WalletDTO $dto): Wallet
    {
        return $this->walletRepository->update($id, $dto);
    }

    public function deleteWallet(string $id): bool
    {
        return $this->walletRepository->delete($id);
    }
}
