<?php

use App\Interfaces\Services\StripeConfigCheckInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/*
 * A Stripe test key in production is reported to the log (once a day, never
 * the key) and shown to admins in the authenticated layout.
 */

const FAKE_TEST_KEY = 'sk_test_finestro_fake_key_for_tests';

beforeEach(function () {
    config(['stripe.secret_key' => FAKE_TEST_KEY]);
});

afterEach(function () {
    app()->detectEnvironment(fn () => 'testing');
});

it('detects a test key in production', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(app(StripeConfigCheckInterface::class)->isTestKeyInProduction())->toBeTrue();
});

it('ignores a test key outside production', function () {
    app()->detectEnvironment(fn () => 'local');

    expect(app(StripeConfigCheckInterface::class)->isTestKeyInProduction())->toBeFalse();
});

it('ignores a live key in production', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['stripe.secret_key' => 'sk_live_finestro_fake']);

    expect(app(StripeConfigCheckInterface::class)->isTestKeyInProduction())->toBeFalse();
});

it('logs critical once across calls and never logs the key', function () {
    app()->detectEnvironment(fn () => 'production');
    Log::spy();

    $check = app(StripeConfigCheckInterface::class);
    $check->reportIfMisconfigured();
    $check->reportIfMisconfigured();

    Log::shouldHaveReceived('critical')->once()->withArgs(
        fn (string $message, array $context = []) => ! str_contains($message.json_encode($context), FAKE_TEST_KEY)
    );
});

it('shows the banner to admins in production', function () {
    app()->detectEnvironment(fn () => 'production');
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('chave de teste do Stripe')
        ->assertDontSee(FAKE_TEST_KEY);
});

it('hides the banner from normal users in production', function () {
    app()->detectEnvironment(fn () => 'production');
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('chave de teste do Stripe')
        ->assertDontSee(FAKE_TEST_KEY);
});

it('hides the banner from admins outside production', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('chave de teste do Stripe');
});
