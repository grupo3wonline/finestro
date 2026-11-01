<?php

declare(strict_types=1);

use App\Interfaces\Services\ConsentServiceInterface;
use App\Models\CookieConsent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Cookie;

/*
|--------------------------------------------------------------------------
| Cookie consent: opt-in, versioned cookie, proof of consent, GPC
|--------------------------------------------------------------------------
*/

/**
 * Consent cookie value as written by ConsentService.
 *
 * @param  array<string, mixed>  $overrides
 */
function consentCookieValue(array $overrides = []): string
{
    return (string) json_encode(array_merge([
        'v' => config('legal.policy_version'),
        'a' => 1,
        'm' => 0,
        'g' => 0,
        't' => now()->getTimestamp(),
    ], $overrides));
}

function queuedConsentCookie(TestResponse $response): ?Cookie
{
    return collect($response->headers->getCookies())
        ->first(fn (Cookie $cookie): bool => $cookie->getName() === 'finestro_consent');
}

beforeEach(function (): void {
    config([
        'services.google.gtm_id' => 'GTM-TEST',
        'services.google.analytics_id' => 'G-TEST',
        'services.facebook.pixel_id' => '123',
    ]);
});

it('loads no analytics or marketing tag before consent', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="finestro-consent-config"', false)
        ->assertDontSee('googletagmanager.com', false)
        ->assertDontSee('connect.facebook.net', false)
        ->assertDontSee('<iframe', false);
});

it('shows reject and accept with the same weight plus a customize option', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('x-data="cookieConsent"', false)
        ->assertSee('Rejeitar não essenciais')
        ->assertSee('Aceitar todos')
        ->assertSee('Personalizar')
        ->assertSee('Global Privacy Control');
});

it('renders the GTM noscript fallback only after analytics consent', function (): void {
    $this->withUnencryptedCookie('finestro_consent', consentCookieValue(['a' => 1]))
        ->get('/')
        ->assertOk()
        ->assertSee('googletagmanager.com/ns.html?id=GTM-TEST', false);

    $this->withUnencryptedCookie('finestro_consent', consentCookieValue(['a' => 0]))
        ->get('/')
        ->assertDontSee('googletagmanager.com', false);
});

it('stores a versioned first-party cookie for guests without a database row', function (): void {
    $response = $this->postJson(route('consent.store'), ['analytics' => true, 'marketing' => true, 'source' => 'banner'])
        ->assertOk()
        ->assertExactJson([
            'analytics' => true,
            'marketing' => true,
            'gpc' => false,
            'version' => config('legal.policy_version'),
        ]);

    $cookie = queuedConsentCookie($response);
    expect($cookie)->not->toBeNull();

    $value = json_decode((string) $cookie->getValue(), true);
    expect($value)->toMatchArray(['v' => config('legal.policy_version'), 'a' => 1, 'm' => 1, 'g' => 0])
        ->and($value['t'])->toBeInt()
        ->and($cookie->isHttpOnly())->toBeFalse()
        ->and($cookie->getSameSite())->toBe('lax')
        ->and($cookie->getExpiresTime())->toBeGreaterThan(now()->addDays(364)->getTimestamp());

    expect(CookieConsent::withoutGlobalScopes()->count())->toBe(0);
});

it('records proof of consent for a logged-in user without personal data in the log', function (): void {
    $user = User::factory()->create(['name' => 'Maria Consentida', 'email' => 'maria@example.com']);

    $this->actingAs($user)
        ->postJson(route('consent.store'), ['analytics' => true, 'marketing' => false, 'source' => 'banner'])
        ->assertOk();

    $row = CookieConsent::withoutGlobalScopes()->where('user_id', $user->id)->sole();
    expect($row->analytics)->toBeTrue()
        ->and($row->marketing)->toBeFalse()
        ->and($row->version)->toBe(config('legal.policy_version'))
        ->and($row->source)->toBe('banner');

    $activity = Activity::query()->where('event', 'consent_updated')->sole();
    $properties = json_encode($activity->properties);

    expect($activity->causer_id)->toBe($user->id)
        ->and($activity->properties->only(['version', 'analytics', 'marketing'])->all())
        ->toBe(['version' => config('legal.policy_version'), 'analytics' => true, 'marketing' => false])
        ->and($properties)->not->toContain('maria@example.com')
        ->and($properties)->not->toContain('Maria Consentida');
});

it('forces marketing off when the browser sends Global Privacy Control', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeader('Sec-GPC', '1')
        ->postJson(route('consent.store'), ['analytics' => true, 'marketing' => true, 'source' => 'banner'])
        ->assertOk()
        ->assertJson(['analytics' => true, 'marketing' => false, 'gpc' => true]);

    expect(json_decode((string) queuedConsentCookie($response)?->getValue(), true))->toMatchArray(['m' => 0, 'g' => 1]);

    $row = CookieConsent::withoutGlobalScopes()->where('user_id', $user->id)->sole();
    expect($row->marketing)->toBeFalse()->and($row->gpc)->toBeTrue();
});

it('treats a cookie from another policy version or a malformed cookie as no consent', function (string $value): void {
    $request = Request::create('/', 'GET', [], ['finestro_consent' => $value]);

    $consent = app(ConsentServiceInterface::class)->fromRequest($request);

    expect($consent->isDecided())->toBeFalse()
        ->and($consent->analytics)->toBeFalse()
        ->and($consent->marketing)->toBeFalse();
})->with([
    'old version' => fn (): string => consentCookieValue(['v' => '2000-01']),
    'not json' => 'aceito',
    'missing timestamp' => fn (): string => consentCookieValue(['t' => null]),
]);

it('reads a valid cookie of the current version', function (): void {
    $request = Request::create('/', 'GET', [], ['finestro_consent' => consentCookieValue(['a' => 1, 'm' => 1])]);
    $service = app(ConsentServiceInterface::class);

    expect($service->fromRequest($request)->isDecided())->toBeTrue()
        ->and($service->allows($request, 'analytics'))->toBeTrue()
        ->and($service->allows($request, 'marketing'))->toBeTrue();
});

it('rejects invalid consent payloads', function (array $payload, string $field): void {
    $this->postJson(route('consent.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'non-boolean analytics' => [['analytics' => 'sim', 'marketing' => false, 'source' => 'banner'], 'analytics'],
    'missing marketing' => [['analytics' => true, 'source' => 'banner'], 'marketing'],
    'unknown source' => [['analytics' => true, 'marketing' => false, 'source' => 'api'], 'source'],
]);

it('saves preferences from a regular form and redirects back', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('settings.privacy'))
        ->post(route('consent.store'), ['analytics' => '0', 'marketing' => '0', 'source' => 'settings'])
        ->assertRedirect(route('settings.privacy'))
        ->assertSessionHas('success');

    expect(CookieConsent::withoutGlobalScopes()->where('user_id', $user->id)->sole()->source)->toBe('settings');
});
