<?php

declare(strict_types=1);

use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Activities\Pages\ViewActivity;
use App\Filament\Resources\PaymentHistories\Pages\ListPaymentHistories;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

/*
|--------------------------------------------------------------------------
| Server-side authorization of panel Livewire components
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->plain = User::factory()->create();
});

it('forbids a plain user from every list page', function (string $page): void {
    $this->actingAs($this->plain, 'admin');

    Livewire::test($page)->assertForbidden();
})->with([
    'users' => [ListUsers::class],
    'subscriptions' => [ListSubscriptions::class],
    'payments' => [ListPaymentHistories::class],
    'activities' => [ListActivities::class],
]);

it('forbids a plain user from the view pages', function (): void {
    $target = User::factory()->create();
    app(SecurityActivityLoggerInterface::class)->log('login', $target);
    $activity = Activity::query()->latest('id')->firstOrFail();

    $this->actingAs($this->plain, 'admin');

    Livewire::test(ViewUser::class, ['record' => $target->getRouteKey()])->assertForbidden();
    Livewire::test(ViewActivity::class, ['record' => $activity->getKey()])->assertForbidden();
});

it('denies create, edit and delete on every resource even for admins', function (): void {
    $this->actingAs(mfaAdmin(), 'admin');

    foreach (Filament::getPanel('admin')->getResources() as $resource) {
        $record = new ($resource::getModel());

        expect($resource::canViewAny())->toBeTrue()
            ->and($resource::canCreate())->toBeFalse()
            ->and($resource::canEdit($record))->toBeFalse()
            ->and($resource::canDelete($record))->toBeFalse()
            ->and($resource::canDeleteAny())->toBeFalse()
            ->and($resource::canForceDelete($record))->toBeFalse()
            ->and($resource::canRestore($record))->toBeFalse()
            ->and($resource::canReplicate($record))->toBeFalse()
            ->and($resource::canReorder())->toBeFalse();
    }
});

it('rejects a table action called by a plain user', function (): void {
    $target = User::factory()->create();
    $this->actingAs(mfaAdmin(), 'admin');

    $component = Livewire::test(ListUsers::class);

    $this->actingAs($this->plain, 'admin');

    $component->call('mountAction', 'suspend', [], ['table' => true, 'recordKey' => $target->getKey()])
        ->assertForbidden();

    expect($target->fresh()->isSuspended())->toBeFalse();
});

it('returns 403 when a plain user replays a Livewire snapshot rendered for an admin', function (): void {
    $target = User::factory()->create();
    $admin = mfaAdmin();

    $html = $this->actingAs($admin, 'admin')->get(ListUsers::getUrl())->assertOk()->getContent();

    preg_match_all('/wire:snapshot="([^"]+)"/', (string) $html, $matches);

    $componentName = app('livewire')->new(ListUsers::class)->getName();

    $snapshot = collect($matches[1])
        ->map(fn (string $encoded): string => html_entity_decode($encoded, ENT_QUOTES))
        ->first(fn (string $json): bool => (json_decode($json, true)['memo']['name'] ?? null) === $componentName);

    expect($snapshot)->toBeString();

    $payload = fn (): array => [
        'components' => [[
            'snapshot' => $snapshot,
            'updates' => [],
            'calls' => [[
                'method' => 'mountAction',
                'params' => ['suspend', [], ['table' => true, 'recordKey' => $target->getKey()]],
                'metadata' => [],
            ]],
        ]],
    ];

    $updateUri = app('livewire')->getUpdateUri();
    $headers = ['X-Livewire' => 'true'];

    // Control: the same replay works for the admin who rendered it.
    $this->actingAs($admin, 'admin')->postJson($updateUri, $payload(), $headers)->assertOk();

    $this->actingAs($this->plain, 'admin')->postJson($updateUri, $payload(), $headers)->assertForbidden();

    expect($target->fresh()->isSuspended())->toBeFalse();
});
