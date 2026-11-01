<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\Repositories\ActivityRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class ActivityRepository implements ActivityRepositoryInterface
{
    public function paginateForUser(string $userId, int $perPage = 20): LengthAwarePaginator
    {
        $userMorph = (new User)->getMorphClass();

        return Activity::query()
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $causer) => $causer
                    ->where('causer_type', $userMorph)
                    ->where('causer_id', $userId))
                ->orWhere(fn (Builder $subject) => $subject
                    ->where('subject_type', $userMorph)
                    ->where('subject_id', $userId)
                    ->whereNull('causer_id')))
            ->latest()
            ->latest('id')
            ->paginate($perPage);
    }

    public function adminQuery(): Builder
    {
        return Activity::query()
            ->with('causer')
            ->latest()
            ->latest('id');
    }
}
