<?php

namespace Tests\Feature;

use Database\Seeders\FaqSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests for the public pages affected by this round of changes:
 * the unified landing (contact channels), the login screen (username/email
 * field) and the Help Center (FAQ served through the service layer).
 */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_with_whatsapp_and_email_channels(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('99692-4829');
        $response->assertSee('contato@3wonline.com.br');
        $response->assertSee('wa.me/5541996924829', false);
    }

    public function test_home_does_not_embed_a_contact_form_iframe(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('freshdesk');
    }

    public function test_login_screen_asks_username_email_and_password(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('name="username"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertDontSee('name="login"', false);
    }

    public function test_faq_page_renders_seeded_content(): void
    {
        $this->seed(FaqSeeder::class);

        $response = $this->get(route('faq'));

        $response->assertOk();
    }

    public function test_legacy_public_routes_redirect_to_landing_anchors(): void
    {
        // Laravel's RedirectController emits the destination as given ("/#sobre");
        // assertRedirect() resolves it against the app URL before comparing.
        $this->get(route('about'))->assertStatus(301)->assertRedirect(url('/#sobre'));
        $this->get(route('services'))->assertStatus(301)->assertRedirect(url('/#servicos'));
        $this->get(route('tools'))->assertStatus(301)->assertRedirect(url('/#ferramentas'));
    }

    public function test_contact_page_lists_direct_channels_without_form_or_iframe(): void
    {
        $response = $this->get(route('contact'));

        $response->assertOk();
        $response->assertSee('99692-4829');
        $response->assertSee('contato@3wonline.com.br');
        $response->assertSee('wa.me/5541996924829', false);
        $response->assertDontSee('<form', false);
        $response->assertDontSee('<iframe', false);
        $response->assertSee('<link rel="canonical" href="'.route('contact').'"', false);
    }

    public function test_robots_and_sitemap_are_available(): void
    {
        $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->get('/sitemap.xml')->assertOk();
    }
}
