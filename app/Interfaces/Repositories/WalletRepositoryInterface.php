<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\DTOs\WalletDTO;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WalletRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Wallet>
     */
    public function getAll(array $filters = []): Collection;

    /**
     * The user's own wallets (scoped), paginated.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Wallet>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?Wallet;

    public function create(WalletDTO $dto): Wallet;

    public function update(string $id, WalletDTO $dto): Wallet;

    public function delete(string $id): bool;

    /**
     * Wallets owned by other users in which the given user is an accepted member.
     *
     * @return Collection<int, Wallet>
     */
    public function getSharedWithUser(string $userId): Collection;

    /**
     * Find a wallet the user owns or is an accepted member of.
     */
    public function findAccessibleById(string $id, string $userId): ?Wallet;

    /**
     * Names of the user's own wallets (soft-deleted included), keyed by id.
     * Ids of other users' wallets are left out.
     *
     * @param  list<string>  $ids
     * @return array<string, array{name: string, deleted: bool}>
     */
    public function namesForUser(string $userId, array $ids): array;
}
