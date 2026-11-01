<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Read-only resource authorization without model policies (registering
 * policies would change Gate behaviour in the user app).
 *
 * Every Filament can*() check (canAccess, canViewAny, canView, canCreate,
 * canEdit, canDelete, canDeleteAny, canForceDelete(Any), canRestore(Any),
 * canReplicate, canReorder) and every default action authorization goes
 * through getAuthorizationResponse(): only `viewAny` / `view` are allowed,
 * and only for a user holding `admin.access`. Writes are custom actions
 * that call the admin services and authorize themselves.
 */
trait RequiresAdminAccess
{
    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $action = $action instanceof UnitEnum ? $action->name : $action;

        if (in_array($action, ['viewAny', 'view'], true) && static::hasAdminAccess()) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * The authenticated panel user holds `admin.access`.
     */
    public static function hasAdminAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('admin.access');
    }
}
