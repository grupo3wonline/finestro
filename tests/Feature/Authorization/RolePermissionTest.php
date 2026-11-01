<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Spatie roles/permissions: seeding, legacy column mapping, registration,
 * permission-based 403s and the role middleware alias.
 */
class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(2, Role::count());
        $this->assertSame(count(RolesAndPermissionsSeeder::PERMISSIONS), Permission::count());
        $this->assertSame(24, Permission::count());
        $this->assertSame(24, Role::findByName('admin', 'web')->permissions()->count());
        $this->assertSame(23, Role::findByName('user', 'web')->permissions()->count());
        $this->assertFalse(Role::findByName('user', 'web')->hasPermissionTo('admin.access'));
    }

    public function test_seeder_maps_legacy_role_column_without_overwriting_existing_roles(): void
    {
        $legacyAdmin = User::factory()->create(['role' => 'admin']);
        $legacyAdmin->syncRoles([]);
        $legacyUser = User::factory()->create(['role' => 'user']);
        $legacyUser->syncRoles([]);
        $promoted = User::factory()->create(['role' => 'user']);
        $promoted->syncRoles(['admin']);

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(['admin'], $legacyAdmin->fresh()->getRoleNames()->all());
        $this->assertSame(['user'], $legacyUser->fresh()->getRoleNames()->all());
        $this->assertSame(['admin'], $promoted->fresh()->getRoleNames()->all());
    }

    public function test_registration_assigns_the_user_role(): void
    {
        $this->post('/register', [
            'name' => 'Nova Pessoa',
            'username' => 'novapessoa',
            'email' => 'nova@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'nova@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_without_role_gets_403_on_own_data(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $user->syncRoles([]);

        $this->actingAs($user);

        $this->get(route('wallets.index'))->assertForbidden();
        $this->get(route('wallets.show', $wallet))->assertForbidden();
        $this->post(route('wallets.store'), ['name' => 'Nova', 'type' => 'checking', 'ownership' => 'personal'])->assertForbidden();
        $this->get(route('transactions.index'))->assertForbidden();
        $this->get(route('bills.index'))->assertForbidden();
        $this->get(route('goals.index'))->assertForbidden();
        $this->get(route('categories.index'))->assertForbidden();
        $this->get(route('budgets.index'))->assertForbidden();
        $this->get(route('reports.index'))->assertForbidden();
        $this->get(route('ai.index'))->assertForbidden();

        $this->assertSame(1, Wallet::withoutGlobalScopes()->count());
    }

    public function test_missing_single_permission_returns_403(): void
    {
        $limited = Role::findOrCreate('limited', 'web');
        $limited->syncPermissions(array_values(array_diff(
            RolesAndPermissionsSeeder::PERMISSIONS,
            ['wallets.delete', 'admin.access']
        )));

        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $user->syncRoles([$limited]);

        $this->actingAs($user)->get(route('wallets.show', $wallet))->assertOk();
        $this->actingAs($user)->delete(route('wallets.destroy', $wallet))->assertForbidden();
        $this->assertNotSoftDeleted('wallets', ['id' => $wallet->id]);
    }

    public function test_role_middleware_alias_distinguishes_admin_and_user(): void
    {
        Route::middleware(['web', 'auth', 'role:admin'])->get('/_acl-test/admin', fn () => 'ok');

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($user->isAdmin());

        $this->actingAs($admin)->get('/_acl-test/admin')->assertOk()->assertSee('ok');
        $this->actingAs($user)->get('/_acl-test/admin')->assertForbidden();
    }

    public function test_admin_does_not_bypass_ownership(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $wallet = Wallet::factory()->for($owner)->create();

        $this->actingAs($admin)->get(route('wallets.show', $wallet))->assertNotFound();
        $this->actingAs($admin)->delete(route('wallets.destroy', $wallet))->assertNotFound();
        $this->assertNotSoftDeleted('wallets', ['id' => $wallet->id]);
    }
}
