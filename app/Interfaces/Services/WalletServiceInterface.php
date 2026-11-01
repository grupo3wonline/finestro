<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\DTOs\WalletDTO;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WalletServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Wallet>
     */
    public function getAllWallets(array $filters = []): Collection;

    /**
     * The current user's own wallets, paginated.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Wallet>
     */
    public function getPaginatedWallets(array $filters, int $perPage): LengthAwarePaginator;

    public function getWalletById(string $id): ?Wallet;

    public function createWallet(WalletDTO $dto): Wallet;

    public function updateWallet(string $id, WalletDTO $dto): Wallet;

    public function deleteWallet(string $id): bool;

    /**
     * Wallets of other users shared with (and accepted by) the given user.
     *
     * @return Collection<int, Wallet>
     */
    public function getSharedWallets(User $user): Collection;

    /**
     * Wallet owned by the user or shared with them as an accepted member.
     */
    public function getAccessibleWallet(string $id, User $user): ?Wallet;
}
