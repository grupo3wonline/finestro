<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cross-user reads for the admin panel (account, access and plan data only).
 */
interface AdminUserRepositoryInterface
{
    /**
     * Every non-deleted user with roles and the current subscription eager loaded.
     *
     * @return Builder<User>
     */
    public function query(): Builder;

    public function countAll(): int;

    public function countCreatedSince(CarbonInterface $since): int;

    /**
     * Users whose current subscription grants the Professional plan.
     */
    public function countProfessional(): int;

    /**
     * Admins that can still act: role admin, not suspended, not deleted.
     */
    public function countActiveAdmins(?string $exceptUserId = null): int;

    /**
     * Select options (id => "name (email)") for users matching name or e-mail.
     *
     * @return array<string, string>
     */
    public function searchOptions(string $term, int $limit = 50): array;

    /**
     * Select option label ("name (email)") of a user, including deleted ones.
     */
    public function optionLabel(string $id): ?string;
}
