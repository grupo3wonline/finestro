<?php

declare(strict_types=1);

use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| /admin access matrix (guard `admin`, canAccessPanel, required MFA)
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('redirects guests to the panel login', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('renders the panel login page for guests', function (): void {
    $this->get('/admin/login')->assertOk()->assertSee('Finestro');
});

it('forbids a plain user', function (): void {
    $user = User::factory()->create();
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

    $this->actingAs($user, 'admin')->get('/admin')->assertForbidden();
});

it('forbids an admin role without the admin.access permission', function (): void {
    $admin = mfaAdmin();

    Role::findByName('admin', 'web')->revokePermissionTo('admin.access');

    $this->actingAs($admin->fresh(), 'admin')->get('/admin')->assertForbidden();
});

it('forbids an unverified admin', function (): void {
    $admin = mfaAdmin(['email_verified_at' => null]);

    $this->actingAs($admin, 'admin')->get('/admin')->assertForbidden();
});

it('forbids a suspended admin', function (): void {
    $admin = mfaAdmin(['suspended_at' => now()]);

    $this->actingAs($admin, 'admin')->get('/admin')->assertForbidden();
});

it('redirects an admin without MFA to the required MFA set-up page', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin, 'admin')
        ->get('/admin')
        ->assertRedirect(Filament::getPanel('admin')->getSetUpRequiredMultiFactorAuthenticationUrl());
});

it('renders the required MFA set-up page for an admin without MFA', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin, 'admin')
        ->get(Filament::getPanel('admin')->getSetUpRequiredMultiFactorAuthenticationUrl())
        ->assertOk();
});

it('lets an admin with MFA open the dashboard', function (): void {
    $this->actingAs(mfaAdmin(), 'admin')
        ->get('/admin')
        ->assertOk()
        ->assertSee('Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL');
});

it('does not accept a user-app (web guard) session in the panel', function (): void {
    $this->actingAs(mfaAdmin(), 'web')->get('/admin')->assertRedirect('/admin/login');
});

/*
| Direct-URL matrix over every resource index and view page: the panel-wide
| Authenticate middleware must deny each URL over HTTP, not only /admin.
*/

/** Resolves a '{user}' / '{activity}' placeholder path to a real record URL. */
function adminResourceUrl(string $path): string
{
    $target = User::factory()->create();
    app(SecurityActivityLoggerInterface::class)->log('login', $target);
    $activity = Activity::query()->latest('id')->firstOrFail();

    return strtr($path, ['{user}' => $target->getKey(), '{activity}' => $activity->getKey()]);
}

dataset('admin resource urls', [
    'users index' => '/admin/users',
    'user view' => '/admin/users/{user}',
    'subscriptions index' => '/admin/subscriptions',
    'payments index' => '/admin/payment-histories',
    'activities index' => '/admin/activities',
    'activity view' => '/admin/activities/{activity}',
]);

dataset('denied personas', [
    'plain user' => function (): User {
        $user = User::factory()->create();
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

        return $user->fresh();
    },
    'admin without admin.access' => function (): User {
        $admin = mfaAdmin();
        Role::findByName('admin', 'web')->revokePermissionTo('admin.access');

        return $admin->fresh();
    },
    'unverified admin' => fn (): User => mfaAdmin(['email_verified_at' => null]),
    'suspended admin' => fn (): User => mfaAdmin(['suspended_at' => now()]),
]);

it('redirects guests from every resource URL to the panel login', function (string $path): void {
    $this->get(adminResourceUrl($path))->assertRedirect('/admin/login');
})->with('admin resource urls');

it('forbids every resource URL to users who fail the access rule', function (User $user, string $path): void {
    $url = adminResourceUrl($path);

    $this->actingAs($user, 'admin')->get($url)->assertForbidden();
})->with('denied personas')->with('admin resource urls');

it('opens every resource URL for an admin with MFA', function (string $path): void {
    $url = adminResourceUrl($path);

    $this->actingAs(mfaAdmin(), 'admin')->get($url)->assertOk();
})->with('admin resource urls');

it('never sends admin names to a third-party avatar service', function (): void {
    $this->actingAs(mfaAdmin(['name' => 'Ana Souza']), 'admin')
        ->get('/admin')
        ->assertOk()
        ->assertDontSee('ui-avatars.com');
});
