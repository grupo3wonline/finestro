<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\Repositories\PersonalDataRepositoryInterface;
use App\Models\Budget;
use App\Models\Category;
use App\Models\CookieConsent;
use App\Models\Goal;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Runs inside a queued job (no authenticated user), so every query drops the
 * global scopes and filters by user_id explicitly.
 */
class PersonalDataRepository implements PersonalDataRepositoryInterface
{
    public function collect(User $user): array
    {
        $userId = (string) $user->id;

        return [
            'account' => [[
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
                'terms_version' => $user->terms_version,
                'terms_accepted_at' => $user->terms_accepted_at?->toIso8601String(),
            ]],
            'wallets' => $this->owned(Wallet::class, $userId),
            'transactions' => $this->owned(Transaction::class, $userId),
            'categories' => $this->owned(Category::class, $userId),
            'goals' => $this->owned(Goal::class, $userId),
            'budgets' => $this->owned(Budget::class, $userId),
            'subscriptions' => $this->owned(Subscription::class, $userId),
            'payments' => $this->owned(PaymentHistory::class, $userId),
            'receipts' => $this->receipts($userId),
            'cookie_consents' => $this->owned(CookieConsent::class, $userId),
            'activity' => $this->activity($userId),
        ];
    }

    /**
     * Raw stored columns of the user's rows of one table.
     *
     * @param  class-string<Model>  $model
     * @return list<array<string, mixed>>
     */
    private function owned(string $model, string $userId): array
    {
        return $model::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->values()
            ->all();
    }

    /**
     * Receipt metadata (no file contents) of the user's transactions.
     *
     * @return list<array<string, mixed>>
     */
    private function receipts(string $userId): array
    {
        return Media::query()
            ->where('model_type', (new Transaction)->getMorphClass())
            ->whereIn('model_id', Transaction::withoutGlobalScopes()->where('user_id', $userId)->select('id'))
            ->orderBy('created_at')
            ->get(['model_id', 'file_name', 'mime_type', 'size', 'created_at'])
            ->map(fn (Media $media): array => [
                'transaction_id' => $media->model_id,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'created_at' => $media->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * Same criteria as ActivityRepository::paginateForUser(), without the
     * stored properties (they may describe other records).
     *
     * @return list<array<string, mixed>>
     */
    private function activity(string $userId): array
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
            ->oldest()
            ->oldest('id')
            ->get(['event', 'log_name', 'created_at'])
            ->map(fn (Activity $activity): array => [
                'event' => $activity->event,
                'log_name' => $activity->log_name,
                'created_at' => $activity->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
