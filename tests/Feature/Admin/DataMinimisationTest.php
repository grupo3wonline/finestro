<?php

declare(strict_types=1);

use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Routing\Route;
use Spatie\Activitylog\Models\Activity;

/*
|--------------------------------------------------------------------------
| The admin panel never exposes users' financial data
|--------------------------------------------------------------------------
*/

it('registers resources only for accounts, billing and activity', function (): void {
    $models = collect(Filament::getPanel('admin')->getResources())
        ->map(fn (string $resource): string => $resource::getModel())
        ->all();

    expect($models)->not->toBeEmpty()
        ->and(array_diff($models, [User::class, Subscription::class, PaymentHistory::class, Activity::class]))->toBe([]);
});

it('has no admin route for financial data', function (): void {
    $uris = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn (Route $route): string => $route->uri())
        ->filter(fn (string $uri): bool => $uri === 'admin' || str_starts_with($uri, 'admin/'))
        ->values();

    expect($uris)->not->toBeEmpty();

    $uris->each(fn (string $uri) => expect($uri)->not->toMatch('/wallet|transaction|goal|budget|categor|receipt|media|ai-/i'));
});

it('keeps global search disabled', function (): void {
    expect(Filament::getPanel('admin')->getGlobalSearchProvider())->toBeNull();
});
