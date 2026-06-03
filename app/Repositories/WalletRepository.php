<?php

namespace App\Repositories;

use App\DTOs\WalletDTO;
use App\Interfaces\Repositories\WalletRepositoryInterface;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;

class WalletRepository implements WalletRepositoryInterface
{
    public function __construct(
        protected Wallet $model
    ) {}

    public function getAll(array $filters = []): Collection
    {
        return $this->model->filter($filters)->orderBy('name')->get();
    }

    public function findById(string $id): ?Wallet
    {
        return $this->model->find($id);
    }

    public function create(WalletDTO $dto): Wallet
    {
        return $this->model->create($dto->toArray());
    }

    public function update(string $id, WalletDTO $dto): Wallet
    {
        $wallet = $this->findById($id);
        $wallet->update($dto->toArray());

        return $wallet->fresh();
    }

    public function delete(string $id): bool
    {
        $wallet = $this->findById($id);

        return $wallet ? $wallet->delete() : false;
    }
}
