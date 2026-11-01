<?php

declare(strict_types=1);

use App\Models\DataExport;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Legal pages (privacy, cookies, terms) and Configurações > Privacidade
|--------------------------------------------------------------------------
*/

it('covers LGPD, GDPR, CCPA/CPRA and POPIA in the privacy policy', function (): void {
    config([
        'legal.controller.legal_name' => '[PREENCHER: Razão social]',
        'privacy.dpo_email' => 'dpo@example.com',
    ]);

    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('Política de Privacidade')
        ->assertSee('LGPD (Brasil), art. 18')
        ->assertSee('GDPR (União Europeia), arts. 15 a 22')
        ->assertSee('CCPA/CPRA (Califórnia, EUA)')
        ->assertSee('POPIA (África do Sul), seções 23 a 25')
        ->assertSee('id="seus-direitos"', false)
        ->assertSee('Não vendemos nem compartilhamos dados pessoais')
        ->assertSee(route('cookies').'#nao-vender', false)
        ->assertSee('[PREENCHER: Razão social]')
        ->assertSee('dado pendente de preenchimento')
        ->assertSee('dpo@example.com')
        ->assertSee('Versão '.config('legal.policy_version'))
        ->assertSee('Groq')
        ->assertSee('Stripe');
});

it('lists every cookie the application sets', function (): void {
    $this->get(route('cookies'))
        ->assertOk()
        ->assertSee('Política de Cookies')
        ->assertSee((string) config('session.cookie'))
        ->assertSee('XSRF-TOKEN')
        ->assertSee('remember_web_*')
        ->assertSee('finestro_consent')
        ->assertSee('darkMode')
        ->assertSee('id="nao-vender"', false)
        ->assertSee(route('consent.store'), false)
        ->assertDontSee('_ga, _ga_*')
        ->assertDontSee('_fbp');
});

it('lists analytics and marketing cookies only when those tags are configured', function (): void {
    config(['services.google.analytics_id' => 'G-TEST', 'services.facebook.pixel_id' => '123']);

    $this->get(route('cookies'))
        ->assertOk()
        ->assertSee('_ga, _ga_*')
        ->assertSee('_fbp');
});

it('renders the terms with the policy version and the plans without prices', function (): void {
    $this->get(route('terms'))
        ->assertOk()
        ->assertSee('Versão '.config('legal.policy_version'))
        ->assertSee('Básico')
        ->assertSee('Profissional')
        ->assertSee(route('privacy'), false)
        ->assertSee(route('contact'), false);
});

it('links the footer to the rights section and the opt-out', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('privacy').'#seus-direitos', false)
        ->assertSee(route('cookies').'#nao-vender', false);
});

it('renders Configurações > Privacidade for free and pro users', function (User $user): void {
    $this->actingAs($user)->get(route('settings.privacy'))
        ->assertOk()
        ->assertSee('Exportar meus dados')
        ->assertSee('Preferências de cookies e marketing')
        ->assertSee('Histórico de consentimento')
        ->assertSee('Excluir minha conta')
        ->assertSee('Versão '.$user->terms_version, false)
        ->assertSee(route('settings.privacy.export'), false)
        ->assertSee(route('settings.account.delete'), false)
        ->assertSee('data-confirm=', false)
        ->assertDontSee('confirm(\'', false);
})->with([
    'free' => fn (): User => freeUser(),
    'pro' => fn (): User => proUser(),
]);

it('requires login for Configurações > Privacidade', function (): void {
    $this->get(route('settings.privacy'))->assertRedirect(route('login'));
});

it('lists only the user own exports with a signed download link', function (): void {
    $user = User::factory()->create();
    $mine = DataExport::factory()->ready()->for($user)->create();
    $other = DataExport::factory()->ready()->create();

    $this->actingAs($user)->get(route('settings.privacy'))
        ->assertOk()
        ->assertSee('Pronta para baixar')
        ->assertSee('exportacao/'.$mine->id.'?expires=', false)
        ->assertDontSee($other->id);
});

it('links the settings page to Privacidade instead of a native confirm danger zone', function (): void {
    $this->actingAs(User::factory()->create())->get(route('settings.index'))
        ->assertOk()
        ->assertSee(route('settings.privacy'), false)
        ->assertDontSee('confirm(\'TEM CERTEZA', false);
});

it('returns to Privacidade with an error when the deletion password is wrong', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('settings.privacy'))
        ->delete(route('settings.account.delete'), ['password' => 'errada'])
        ->assertRedirect(route('settings.privacy'))
        ->assertSessionHasErrors('password');

    $this->assertNotSoftDeleted($user);
});
