<?php

declare(strict_types=1);

use App\Enums\DataExportStatus;
use App\Http\Middleware\EnsureTermsAccepted;
use App\Interfaces\Services\DataExportServiceInterface;
use App\Models\DataExport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| Terms / Privacy Policy versioning (config('legal.policy_version'))
|--------------------------------------------------------------------------
*/

it('lets a user who accepted the current version reach the dashboard', function (): void {
    $user = User::factory()->create();

    expect($user->terms_version)->toBe(config('legal.policy_version'));

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

it('asks a user who accepted an older version to accept again', function (): void {
    $user = User::factory()->create();
    $user->forceFill(['terms_version' => '2000-01'])->save();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('terms.accept'));

    $this->get(route('terms.accept'))
        ->assertOk()
        ->assertSee('Atualizamos nossos Termos de Uso')
        ->assertSee(config('legal.policy_version'));
});

it('does not show the update notice on a first acceptance', function (): void {
    $user = User::factory()->withoutTermsAccepted()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('terms.accept'));

    $this->get(route('terms.accept'))->assertOk()->assertDontSee('Atualizamos nossos Termos de Uso');
});

it('stores the current version and logs terms_accepted on acceptance', function (): void {
    $user = User::factory()->create();
    $user->forceFill(['terms_version' => '2000-01'])->save();

    $this->actingAs($user)
        ->post(route('terms.store'), ['terms' => '1'])
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->terms_version)->toBe(config('legal.policy_version'));

    $activity = Activity::query()->where('log_name', 'security')->where('event', 'terms_accepted')->latest()->first();
    expect($activity)->not->toBeNull()
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->getProperty('version'))->toBe(config('legal.policy_version'));

    $this->get(route('dashboard'))->assertOk();
});

it('rejects acceptance without the checkbox', function (): void {
    $user = User::factory()->withoutTermsAccepted()->create();

    $this->actingAs($user)
        ->from(route('terms.accept'))
        ->post(route('terms.store'), [])
        ->assertSessionHasErrors('terms');

    expect($user->fresh()->terms_version)->toBeNull();
});

/*
| Data subject rights never depend on accepting a new policy version.
*/

const DSR_ROUTES_OUTSIDE_TERMS_GATE = [
    'settings.privacy',
    'settings.privacy.export',
    'settings.privacy.download',
    'settings.account.delete',
];

function userOnOldPolicyVersion(): User
{
    $user = User::factory()->create();
    $user->forceFill(['terms_version' => '2000-01'])->save();

    return $user;
}

it('links the accept-terms page to Configurações > Privacidade', function (): void {
    $this->actingAs(userOnOldPolicyVersion())
        ->get(route('terms.accept'))
        ->assertOk()
        ->assertSee('Prefiro exportar ou excluir meus dados')
        ->assertSee(route('settings.privacy'), false);
});

it('lets a user on an old policy version export and download their data without accepting', function (): void {
    Storage::fake('local');
    $user = userOnOldPolicyVersion();

    $this->actingAs($user)->get(route('settings.privacy'))->assertOk()->assertSee('Privacidade e seus dados');

    $this->post(route('settings.privacy.export'))
        ->assertRedirect(route('settings.privacy'))
        ->assertSessionHas('success');

    $export = DataExport::withoutGlobalScopes()->where('user_id', $user->id)->sole();
    expect($export->status)->toBe(DataExportStatus::Ready);

    // Safeguards still apply: unsigned link refused, signed link served.
    $this->get(route('settings.privacy.download', $export))->assertForbidden();
    $this->get(app(DataExportServiceInterface::class)->downloadUrl($export))->assertOk()->assertDownload();

    expect($user->fresh()->terms_version)->toBe('2000-01');
});

it('lets a user on an old policy version delete the account without accepting, still with the password', function (): void {
    $user = userOnOldPolicyVersion();

    $this->actingAs($user)
        ->from(route('settings.privacy'))
        ->delete(route('settings.account.delete'), ['password' => 'errada'])
        ->assertRedirect(route('settings.privacy'))
        ->assertSessionHasErrors('password');

    $this->delete(route('settings.account.delete'), ['password' => 'password'])
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect(User::withTrashed()->find($user->id)?->trashed())->toBeTrue();
});

it('still requires login and e-mail verification on the data rights routes', function (): void {
    $this->get(route('settings.privacy'))->assertRedirect(route('login'));

    $unverified = User::factory()->unverified()->create();
    $this->actingAs($unverified)->get(route('settings.privacy'))->assertRedirect(route('verification.notice'));
});

it('keeps every other app route behind the terms gate', function (): void {
    $gated = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn (Route $route): bool => in_array('terms.accepted', $route->gatherMiddleware(), true));

    $user = userOnOldPolicyVersion();
    $this->actingAs($user);
    // ~60 requests from one user in one test would trip per-user throttles; throttling is not under test here.
    $this->withoutMiddleware(ThrottleRequests::class);
    $middleware = app(EnsureTermsAccepted::class);
    $passed = [];
    $httpChecked = 0;

    foreach ($gated as $route) {
        $name = (string) $route->getName();

        // Middleware decision for every gated route, including those with
        // parameters (their bindings resolve before this middleware runs).
        // Identity check on the sentinel avoids coupling to a response body.
        $sentinel = response('passed');
        $request = Request::create('/'.ltrim($route->uri(), '/'));
        $request->setRouteResolver(fn (): Route => $route);
        $response = $middleware->handle($request, fn (): Response => $sentinel);

        if ($response === $sentinel) {
            $passed[] = $name;
        } else {
            expect($response->isRedirect(route('terms.accept')))->toBeTrue("{$name} should redirect to terms.accept");
        }

        // End-to-end request for every parameterless gated route (all methods).
        if (in_array($name, DSR_ROUTES_OUTSIDE_TERMS_GATE, true) || $route->parameterNames() !== []) {
            continue;
        }

        $method = collect($route->methods())->reject(fn (string $m): bool => $m === 'HEAD')->first();

        $response = $this->call((string) $method, '/'.ltrim($route->uri(), '/'));

        expect($response->isRedirect(route('terms.accept')))
            ->toBeTrue("{$method} {$name} answered {$response->getStatusCode()} instead of redirecting to terms.accept");

        $httpChecked++;
    }

    // Every data rights route must pass without accepting a new version, and
    // nothing passes except routes the middleware explicitly exempts. This
    // stays green if the middleware's exemption list grows, as long as the
    // DSR routes keep passing.
    $middlewareExempt = [
        'terms.accept', 'terms.show', 'logout', 'terms', 'privacy',
        ...DSR_ROUTES_OUTSIDE_TERMS_GATE,
    ];

    expect(array_values(array_diff($passed, $middlewareExempt)))->toBe([])
        ->and(array_values(array_intersect(DSR_ROUTES_OUTSIDE_TERMS_GATE, $passed)))
        ->toEqualCanonicalizing(DSR_ROUTES_OUTSIDE_TERMS_GATE)
        ->and($httpChecked)->toBeGreaterThan(40);
});

it('stores the current policy version on registration', function (): void {
    $this->post('/register', [
        'name' => 'Teste Versão',
        'username' => 'teste_versao',
        'email' => 'versao@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
    ])->assertRedirect(route('login'));

    expect(User::query()->where('email', 'versao@example.com')->value('terms_version'))
        ->toBe(config('legal.policy_version'));
});
