<?php

use App\Models\Subscription;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Applies only to Pest closure tests; class-based PHPUnit tests are unaffected (they extend TestCase and use RefreshDatabase themselves).
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| REST API helpers
|--------------------------------------------------------------------------
*/

/** A verified user on the Professional plan (has `api_access`). */
function proUser(): User
{
    $user = User::factory()->create();
    Subscription::factory()->for($user)->create();

    return $user->fresh();
}

/** A verified user on the Free plan (no `api_access`). */
function freeUser(): User
{
    return User::factory()->create();
}

/**
 * Real Sanctum personal access token (plain text "{id}|{secret}").
 *
 * @param  list<string>  $abilities
 */
function apiToken(User $user, array $abilities = ['*']): string
{
    return $user->createToken('Pest', $abilities)->plainTextToken;
}

/*
|--------------------------------------------------------------------------
| Admin panel helpers
|--------------------------------------------------------------------------
*/

/**
 * A verified admin with app (TOTP) MFA set up, so /admin does not redirect
 * to the required MFA set-up page. The plain secret is the TOTP seed.
 *
 * @param  array<string, mixed>  $attributes
 */
function mfaAdmin(array $attributes = []): User
{
    $admin = User::factory()->admin()->create($attributes);
    $admin->saveAppAuthenticationSecret(app(AppAuthentication::class)->generateSecret());

    return $admin->fresh();
}
