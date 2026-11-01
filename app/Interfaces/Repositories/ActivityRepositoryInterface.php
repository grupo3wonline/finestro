<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

interface ActivityRepositoryInterface
{
    /**
     * Activities caused by the user, plus activities about the user that have
     * no causer (failed logins against the account), newest first.
     *
     * @return LengthAwarePaginator<int, Activity>
     */
    public function paginateForUser(string $userId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Every activity for the admin panel, causer eager loaded, newest first.
     *
     * @return Builder<Activity>
     */
    public function adminQuery(): Builder;
}
