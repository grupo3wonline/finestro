<?php

namespace App\Traits;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Activity logging for models owned by a user (`user_id` column).
 *
 * Only the attributes returned by activityLogAttributes() are recorded, and
 * only when they actually change. When no user is authenticated (scheduler,
 * queue, Stripe webhook) the owner becomes the causer and the activity gets
 * the property `via=system`, so every model activity has a causer and shows
 * up on the owner's "Atividade da conta" page.
 */
trait LogsOwnedActivity
{
    use LogsActivity;

    /**
     * Allowlist of attributes that may be written to the activity log.
     * Never include secrets, balances or documents.
     *
     * @return list<string>
     */
    abstract protected function activityLogAttributes(): array;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->activityLogAttributes())
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('model');
    }

    /**
     * Hook called by the package right before the activity is saved.
     */
    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        $ownerId = $this->getAttribute('user_id');

        if ($activity->getAttribute('causer_id') !== null || blank($ownerId)) {
            return;
        }

        $activity->setAttribute('causer_type', (new User)->getMorphClass());
        $activity->setAttribute('causer_id', (string) $ownerId);
        $activity->properties = ($activity->properties ?? collect())->put('via', 'system');
    }
}
