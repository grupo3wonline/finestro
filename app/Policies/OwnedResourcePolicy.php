<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared authorization rules for user-owned records.
 *
 * Convention (see docs/security/phase-1-acl-audit.md):
 *  - Missing Spatie permission         -> 403 (Response::deny)
 *  - Record belongs to another user    -> 404 (Response::denyAsNotFound),
 *    so the existence of foreign records is never disclosed.
 *
 * Admins do not bypass ownership: there is no Gate::before override.
 */
abstract class OwnedResourcePolicy
{
    /**
     * Permission prefix, e.g. "wallets" for "wallets.view".
     */
    protected string $permissionPrefix;

    public function viewAny(User $user): Response
    {
        return $this->permits($user, 'view');
    }

    public function create(User $user): Response
    {
        return $this->permits($user, 'create');
    }

    public function view(User $user, Model $model): Response
    {
        return $this->ownedAndPermitted($user, $model, 'view');
    }

    public function update(User $user, Model $model): Response
    {
        return $this->ownedAndPermitted($user, $model, 'update');
    }

    public function delete(User $user, Model $model): Response
    {
        return $this->ownedAndPermitted($user, $model, 'delete');
    }

    public function restore(User $user, Model $model): Response
    {
        return $this->ownedAndPermitted($user, $model, 'delete');
    }

    protected function permits(User $user, string $action): Response
    {
        return $user->checkPermissionTo("{$this->permissionPrefix}.{$action}")
            ? Response::allow()
            : Response::deny('Você não tem permissão para realizar esta ação.');
    }

    protected function owns(User $user, Model $model): bool
    {
        return (string) $model->getAttribute('user_id') === (string) $user->getKey();
    }

    protected function ownedAndPermitted(User $user, Model $model, string $action): Response
    {
        $permission = $this->permits($user, $action);

        if ($permission->denied()) {
            return $permission;
        }

        return $this->owns($user, $model) ? Response::allow() : Response::denyAsNotFound();
    }
}
