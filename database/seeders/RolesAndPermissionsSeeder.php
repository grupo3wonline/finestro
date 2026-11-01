<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the ACL roles/permissions and maps the legacy `users.role` column
 * to Spatie roles. Idempotent: safe to run any number of times.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public const GUARD = 'web';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    /** Permissions granted only to administrators. */
    public const ADMIN_ONLY_PERMISSIONS = [
        'admin.access',
    ];

    /** Every permission known by the application. */
    public const PERMISSIONS = [
        'wallets.view', 'wallets.create', 'wallets.update', 'wallets.delete',
        'transactions.view', 'transactions.create', 'transactions.update', 'transactions.delete',
        'goals.view', 'goals.create', 'goals.update', 'goals.delete',
        'budgets.view', 'budgets.create', 'budgets.update', 'budgets.delete',
        'categories.view', 'categories.create', 'categories.update', 'categories.delete',
        'reports.view', 'reports.export',
        'ai.use',
        'admin.access',
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, self::GUARD);
        }

        Role::findOrCreate(self::ROLE_ADMIN, self::GUARD)->syncPermissions(self::PERMISSIONS);
        Role::findOrCreate(self::ROLE_USER, self::GUARD)
            ->syncPermissions(array_values(array_diff(self::PERMISSIONS, self::ADMIN_ONLY_PERMISSIONS)));

        // Map the legacy column only for users without any role yet, so roles
        // granted later are never overwritten by a re-run.
        User::withTrashed()->whereDoesntHave('roles')->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                $user->assignRole($user->role === self::ROLE_ADMIN ? self::ROLE_ADMIN : self::ROLE_USER);
            }
        });

        $registrar->forgetCachedPermissions();
    }
}
