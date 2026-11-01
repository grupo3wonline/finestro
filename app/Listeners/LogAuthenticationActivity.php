<?php

namespace App\Listeners;

use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;
use Spatie\Permission\PermissionRegistrar;

/**
 * Writes authentication and role events to the `security` activity log.
 * Only the event name, the role names and the IP address are stored;
 * credentials of failed logins are never read.
 */
class LogAuthenticationActivity
{
    public function __construct(
        protected SecurityActivityLoggerInterface $logger,
    ) {}

    public function handleLogin(Login $event): void
    {
        $this->logger->log('login', $this->asUser($event->user));
    }

    public function handleLogout(Logout $event): void
    {
        $user = $this->asUser($event->user);

        if ($user instanceof User) {
            $this->logger->log('logout', $user);
        }
    }

    public function handleFailed(Failed $event): void
    {
        $this->logger->log('login_failed', $this->asUser($event->user));
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->logger->log('password_reset', $this->asUser($event->user));
    }

    public function handleRegistered(Registered $event): void
    {
        $this->logger->log('registered', $this->asUser($event->user));
    }

    public function handleRoleAttached(RoleAttachedEvent $event): void
    {
        $this->logRoleChange('role_attached', $event->model, $event->rolesOrIds);
    }

    public function handleRoleDetached(RoleDetachedEvent $event): void
    {
        $this->logRoleChange('role_detached', $event->model, $event->rolesOrIds);
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            PasswordReset::class => 'handlePasswordReset',
            Registered::class => 'handleRegistered',
            RoleAttachedEvent::class => 'handleRoleAttached',
            RoleDetachedEvent::class => 'handleRoleDetached',
        ];
    }

    private function logRoleChange(string $event, mixed $model, mixed $rolesOrIds): void
    {
        $user = $this->asUser($model);

        if (! $user instanceof User) {
            return;
        }

        $this->logger->log($event, $user, ['roles' => $this->roleNames($rolesOrIds)]);
    }

    /**
     * Resolve role names from what HasRoles passes (ids, Role models or a collection).
     *
     * @return list<string>
     */
    private function roleNames(mixed $rolesOrIds): array
    {
        $items = match (true) {
            $rolesOrIds instanceof Model => [$rolesOrIds],
            $rolesOrIds instanceof Collection => $rolesOrIds->all(),
            is_array($rolesOrIds) => $rolesOrIds,
            default => [$rolesOrIds],
        };

        $names = [];
        $ids = [];

        foreach ($items as $item) {
            if ($item instanceof Model) {
                $names[] = (string) $item->getAttribute('name');
            } elseif (is_int($item) || is_string($item)) {
                $ids[] = $item;
            }
        }

        if ($ids !== []) {
            $roleClass = app(PermissionRegistrar::class)->getRoleClass();
            $names = array_merge($names, $roleClass::query()->whereKey($ids)->pluck('name')->map(fn ($name): string => (string) $name)->all());
        }

        return array_values(array_unique($names));
    }

    private function asUser(mixed $user): ?User
    {
        return $user instanceof User ? $user : null;
    }
}
