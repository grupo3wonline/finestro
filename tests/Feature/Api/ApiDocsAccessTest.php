<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

/*
 * Swagger UI (/api/documentation) and the JSON spec (/docs) are open in the
 * local environment; elsewhere only admins see them (guest 404, user 403).
 */

beforeEach(function (): void {
    // The JSON route serves the generated file (generate_always is false).
    Artisan::call('l5-swagger:generate');
});

afterEach(function (): void {
    app()->detectEnvironment(fn (): string => 'testing');
});

dataset('docsRoutes', [
    'swagger ui' => ['l5-swagger.default.api'],
    'json spec' => ['l5-swagger.default.docs'],
]);

it('is open to guests in the local environment', function (string $route): void {
    app()->detectEnvironment(fn (): string => 'local');

    $this->get(route($route))->assertOk();
})->with('docsRoutes');

it('hides the docs from guests in production', function (string $route): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->get(route($route))->assertNotFound();
})->with('docsRoutes');

it('forbids non-admin users in production', function (string $route): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->actingAs(User::factory()->create())->get(route($route))->assertForbidden();
})->with('docsRoutes');

it('allows admins in production', function (string $route): void {
    app()->detectEnvironment(fn (): string => 'production');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route($route))->assertOk();
})->with('docsRoutes');

it('serves the generated spec to admins', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route('l5-swagger.default.docs'))
        ->assertOk()
        ->assertJsonPath('info.title', 'Finestro API');
});
