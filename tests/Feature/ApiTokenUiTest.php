<?php

use App\Models\Subscription;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Activitylog\Models\Activity;

/*
 * Settings > Tokens de API: create (shown once), list own, revoke own,
 * plan gating and activity logging without secrets.
 */

function tokenUiProUser(): User
{
    $user = User::factory()->create();
    Subscription::factory()->for($user)->create();

    return $user->fresh();
}

function createTokenViaUi(User $user, array $payload = []): string
{
    $response = test()->actingAs($user)->post(route('settings.tokens.store'), $payload + [
        'name' => 'Planilha Google',
        'abilities' => ['wallets:read', 'transactions:write'],
    ]);
    $response->assertRedirect(route('settings.tokens.index'));

    return (string) session('plain_token');
}

it('shows the create form to Professional users', function () {
    $this->actingAs(tokenUiProUser())->get(route('settings.tokens.index'))
        ->assertOk()
        ->assertSee('Tokens de API')
        ->assertSee('Criar token')
        ->assertSee('name="abilities[]"', false)
        ->assertDontSee('Fazer upgrade para o Profissional');
});

it('shows the upgrade CTA and no form to Free users', function () {
    $this->actingAs(User::factory()->create())->get(route('settings.tokens.index'))
        ->assertOk()
        ->assertSee('Fazer upgrade para o Profissional')
        ->assertSee(route('subscription.plans'), false)
        ->assertDontSee('name="abilities[]"', false);
});

it('links to the tokens page from settings', function () {
    $this->actingAs(User::factory()->create())->get(route('settings.index'))
        ->assertOk()
        ->assertSee(route('settings.tokens.index'), false);
});

it('creates a token shown exactly once and stores only its hash', function () {
    $user = tokenUiProUser();

    $plain = createTokenViaUi($user);
    expect($plain)->toContain('|');

    $this->get(route('settings.tokens.index'))->assertOk()->assertSee($plain, false);
    $this->get(route('settings.tokens.index'))->assertOk()->assertDontSee($plain, false);

    [$id, $secret] = explode('|', $plain, 2);
    $row = PersonalAccessToken::findOrFail($id);

    expect($row->token)->not->toBe($secret)
        ->and($row->token)->toBe(hash('sha256', $secret))
        ->and($row->abilities)->toBe(['wallets:read', 'transactions:write'])
        ->and($row->name)->toBe('Planilha Google')
        ->and($row->tokenable_id)->toBe($user->id)
        ->and($row->expires_at)->not->toBeNull();
});

it('validates the name and abilities', function (array $payload, string $errorKey) {
    $this->actingAs(tokenUiProUser())
        ->post(route('settings.tokens.store'), $payload)
        ->assertSessionHasErrors($errorKey);

    expect(PersonalAccessToken::count())->toBe(0);
})->with([
    'missing name' => [['abilities' => ['wallets:read']], 'name'],
    'name too long' => [['name' => str_repeat('a', 61), 'abilities' => ['wallets:read']], 'name'],
    'no abilities' => [['name' => 'x', 'abilities' => []], 'abilities'],
    'invalid ability' => [['name' => 'x', 'abilities' => ['*']], 'abilities.0'],
    'unknown ability' => [['name' => 'x', 'abilities' => ['users:admin']], 'abilities.0'],
]);

it('does not let Free users create tokens', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('settings.tokens.store'), ['name' => 'x', 'abilities' => ['wallets:read']])
        ->assertRedirect(route('settings.tokens.index'))
        ->assertSessionHas('error')
        ->assertSessionMissing('plain_token');

    expect(PersonalAccessToken::count())->toBe(0);
});

it('lists only the user own tokens', function () {
    $user = tokenUiProUser();
    $user->createToken('Meu token', ['wallets:read']);
    User::factory()->create()->createToken('Token do vizinho', ['wallets:read']);

    $this->actingAs($user)->get(route('settings.tokens.index'))
        ->assertOk()
        ->assertSee('Meu token')
        ->assertSee('Nunca usado')
        ->assertDontSee('Token do vizinho');
});

it('revokes an own token', function () {
    $user = tokenUiProUser();
    $token = $user->createToken('Revogar', ['wallets:read'])->accessToken;

    $this->actingAs($user)->delete(route('settings.tokens.destroy', $token->id))
        ->assertRedirect(route('settings.tokens.index'))
        ->assertSessionHas('success');

    expect(PersonalAccessToken::find($token->id))->toBeNull();
});

it('lets a downgraded Free user revoke old tokens', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Antigo', ['wallets:read'])->accessToken;

    $this->actingAs($user)->get(route('settings.tokens.index'))->assertSee('Antigo');
    $this->delete(route('settings.tokens.destroy', $token->id))->assertRedirect();

    expect(PersonalAccessToken::find($token->id))->toBeNull();
});

it('returns 404 when revoking another user token or an unknown id', function () {
    $foreign = User::factory()->create()->createToken('Alheio', ['wallets:read'])->accessToken;
    $user = tokenUiProUser();

    $this->actingAs($user)->delete(route('settings.tokens.destroy', $foreign->id))->assertNotFound();
    $this->actingAs($user)->delete(route('settings.tokens.destroy', 999999))->assertNotFound();

    expect(PersonalAccessToken::find($foreign->id))->not->toBeNull();
});

it('logs creation and revocation without the token value or hash', function () {
    $user = tokenUiProUser();
    $plain = createTokenViaUi($user);
    [$id, $secret] = explode('|', $plain, 2);
    $hash = PersonalAccessToken::findOrFail($id)->token;

    $this->delete(route('settings.tokens.destroy', $id))->assertRedirect();

    $created = Activity::query()->where('event', 'api_token_created')->first();
    $revoked = Activity::query()->where('event', 'api_token_revoked')->first();

    expect($created)->not->toBeNull()
        ->and($created->getProperty('token_name'))->toBe('Planilha Google')
        ->and($created->getProperty('abilities'))->toBe(['wallets:read', 'transactions:write'])
        ->and($revoked)->not->toBeNull()
        ->and($revoked->getProperty('token_name'))->toBe('Planilha Google');

    $json = Activity::query()->get()->toJson();
    expect($json)->not->toContain($secret)->not->toContain($hash);

    $this->get(route('settings.activity'))
        ->assertOk()
        ->assertSee('Criou um token de API')
        ->assertSee('Revogou um token de API')
        ->assertSee('Planilha Google')
        ->assertDontSee($secret, false)
        ->assertDontSee($hash, false);
});

it('redirects guests to login', function (string $method, string $uri) {
    $this->call($method, $uri)->assertRedirect(route('login'));
})->with([
    'index' => ['GET', '/configuracoes/tokens'],
    'store' => ['POST', '/configuracoes/tokens'],
    'destroy' => ['DELETE', '/configuracoes/tokens/1'],
]);

it('gives the api_access feature only to Professional users', function () {
    expect(tokenUiProUser()->hasFeature('api_access'))->toBeTrue()
        ->and(User::factory()->create()->hasFeature('api_access'))->toBeFalse();
});
