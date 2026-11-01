<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\DTOs\ActivityEntryData;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface AccountActivityServiceInterface
{
    /**
     * The user's own activity log, translated to pt-BR entries.
     *
     * @return LengthAwarePaginator<int, ActivityEntryData>
     */
    public function paginateForUser(User $user, int $perPage = 20): LengthAwarePaginator;
}
