<?php

use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Interfaces\Services\StripeGatewayInterface;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Password;
use Spatie\Activitylog\Models\Activity;
use Tests\Fakes\FakeStripeGateway;

/*
 * Activity log: allowlisted model changes, auth/security events, causer
 * rules and the per-user "Atividade da conta" page.
 */

beforeEach(function () {
    $this->app->instance(StripeGatewayInterface::class, new FakeStripeGateway);
});

/** Every activity row serialized, to assert secrets never reach the log. */
function allActivityRowsJson(): string
{
    return Activity::query()->get()->toJson();
}

function latestActivity(string $event, ?string $logName = null): ?Activity
{
    return Activity::query()
        ->where('event', $event)
        ->when($logName, fn ($query) => $query->where('log_name', $logName))
        ->latest('id')
        ->first();
}

it('logs a created wallet with the user as causer and only allowlisted attributes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('wallets.store'), [
        'name' => 'Carteira Empresa',
        'type' => 'checking',
        'ownership' => 'business',
        'company_name' => 'Minha Empresa',
        'company_document' => '12.345.678/0001-90',
        'balance' => '500.00',
    ])->assertRedirect();

    $wallet = Wallet::withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();
    $activity = Activity::query()->where('event', 'created')
        ->where('subject_type', $wallet->getMorphClass())
        ->where('subject_id', $wallet->id)
        ->firstOrFail();

    $attributes = $activity->attribute_changes['attributes'];

    expect($activity->causer_id)->toBe($user->id)
        ->and($activity->causer_type)->toBe($user->getMorphClass())
        ->and($activity->log_name)->toBe('model')
        ->and($attributes['name'])->toBe('Carteira Empresa')
        ->and(array_keys($attributes))->not->toContain('balance', 'company_document', 'user_id', 'created_at')
        ->and($activity->getProperty('via'))->toBeNull()
        ->and(allActivityRowsJson())->not->toContain('12.345.678/0001-90');
});

it('logs only the dirty fields of an updated transaction and never the wallet balance', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['type' => 'checking', 'balance' => 1000]);

    $this->actingAs($user)->post(route('transactions.store'), [
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => '100.00',
        'description' => 'Conta de teste',
        'date' => now()->toDateString(),
        'notes' => 'Observação particular',
    ])->assertRedirect();

    $transaction = Transaction::firstOrFail();

    $this->actingAs($user)->put(route('transactions.update', $transaction), [
        'amount' => '150.00',
        'description' => 'Conta corrigida',
        'reason' => 'Valor conforme nota fiscal',
        '_has_recurring_field' => 1,
    ])->assertRedirect();

    $activity = latestActivity('updated', 'model');

    expect($activity->subject_id)->toBe($transaction->id)
        ->and($activity->causer_id)->toBe($user->id)
        ->and(array_keys($activity->attribute_changes['attributes']))->toEqualCanonicalizing(['amount', 'description'])
        ->and($activity->attribute_changes['old'])->toBe(['amount' => '100.00', 'description' => 'Conta de teste'])
        // Balance moves are not logged: no wallet "updated" activity at all.
        ->and(Activity::query()->where('subject_type', $wallet->getMorphClass())->where('event', 'updated')->exists())->toBeFalse()
        ->and(allActivityRowsJson())->not->toContain('Observação particular');
});

it('never writes AI API keys or Stripe ids to the activity log', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->for($user)->create(['current_period_end' => now()->addMonth()]);

    $this->actingAs($user->fresh())->post(route('ai.credentials.save'), [
        'gemini_api_key' => 'gemini-secret-key-123',
        'groq_api_key' => 'groq-secret-key-456',
        'openrouter_api_key' => null,
        'preferred_provider' => 'gemini',
    ])->assertRedirect(route('ai.settings'));

    $json = allActivityRowsJson();

    expect($json)->not->toContain('gemini-secret-key-123')
        ->and($json)->not->toContain('groq-secret-key-456')
        ->and($json)->not->toContain($subscription->stripe_subscription_id)
        ->and($json)->not->toContain($subscription->stripe_customer_id)
        ->and($json)->not->toContain($subscription->stripe_price_id)
        ->and(Activity::query()->where('subject_type', 'App\\Models\\AiCredential')->exists())->toBeFalse()
        ->and(Activity::query()->where('subject_type', $subscription->getMorphClass())->where('event', 'created')->exists())->toBeTrue();
});

it('logs a login with the IP and no password', function () {
    $user = User::factory()->create();

    $this->post('/login', ['username' => $user->username, 'email' => $user->email, 'password' => 'password'])->assertRedirect();

    $activity = latestActivity('login', 'security');

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->getProperty('ip'))->toBe('127.0.0.1')
        ->and($activity->toJson())->not->toContain('password');
});

it('logs a failed login without causer and without the attempted credentials', function () {
    $user = User::factory()->create();

    $this->post('/login', ['username' => $user->username, 'email' => $user->email, 'password' => 'wrong-secret-789'])
        ->assertSessionHasErrors('login');

    $activity = latestActivity('login_failed', 'security');

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull()
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->getProperty('ip'))->toBe('127.0.0.1')
        ->and(allActivityRowsJson())->not->toContain('wrong-secret-789')
        ->and($activity->toJson())->not->toContain('password')
        ->and($activity->toJson())->not->toContain('credentials');
});

it('logs a logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect();

    expect(latestActivity('logout', 'security')?->causer_id)->toBe($user->id);
});

it('logs registration and the default role attachment', function () {
    $this->post('/register', [
        'name' => 'Nova Pessoa',
        'username' => 'novapessoa',
        'email' => 'nova@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
    ])->assertRedirect();

    $user = User::where('email', 'nova@example.com')->firstOrFail();
    $registered = latestActivity('registered', 'security');
    $role = latestActivity('role_attached', 'security');

    expect($registered->subject_id)->toBe($user->id)
        ->and($registered->causer_id)->toBe($user->id)
        ->and($role->subject_id)->toBe($user->id)
        ->and($role->getProperty('roles'))->toBe(['user'])
        ->and(allActivityRowsJson())->not->toContain($user->password);
});

it('logs a password reset', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'nova-senha-segura',
        'password_confirmation' => 'nova-senha-segura',
    ])->assertSessionHasNoErrors();

    $activity = latestActivity('password_reset', 'security');

    expect($activity->subject_id)->toBe($user->id)
        ->and($activity->causer_id)->toBe($user->id)
        ->and(allActivityRowsJson())->not->toContain($token)
        ->and(allActivityRowsJson())->not->toContain('nova-senha-segura');
});

it('logs a password change from the settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('settings.password'), [
        'current_password' => 'password',
        'password' => 'outra-senha-forte',
        'password_confirmation' => 'outra-senha-forte',
    ])->assertSessionHasNoErrors();

    expect(latestActivity('password_changed', 'security')?->causer_id)->toBe($user->id)
        ->and(allActivityRowsJson())->not->toContain('outra-senha-forte');
});

it('logs the account deletion', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->delete(route('settings.account.delete'), ['password' => 'password'])
        ->assertRedirect();

    $this->assertSoftDeleted($user);

    $activity = latestActivity('account_deleted', 'security');

    expect($activity->subject_id)->toBe($user->id)
        ->and($activity->causer_id)->toBe($user->id);
});

it('records an explicit causer instead of the authenticated user', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($other);

    app(SecurityActivityLoggerInterface::class)->log('admin_user_suspended', $target, ['role' => 'user'], $admin);

    $activity = latestActivity('admin_user_suspended', 'security');

    expect($activity->causer_id)->toBe($admin->id)
        ->and($activity->subject_id)->toBe($target->id)
        ->and($activity->getProperty('role'))->toBe('user')
        ->and($activity->getProperty('ip'))->toBe('127.0.0.1');
});

it('keeps the authenticated user as causer when no explicit causer is given', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($actor);

    app(SecurityActivityLoggerInterface::class)->log('password_changed', $target);

    expect(latestActivity('password_changed', 'security')->causer_id)->toBe($actor->id);
});

it('attributes unauthenticated changes to the owner with via=system', function () {
    Carbon::setTestNow('2026-03-10');

    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['type' => 'checking', 'balance' => 1000]);
    $source = Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'status' => 'paid',
        'description' => 'Academia',
        'amount' => 10,
        'date' => '2026-03-08',
        'is_recurring' => true,
        'recurring_frequency' => 'daily',
    ]);

    $this->artisan('transactions:process-recurring')->assertSuccessful();

    $generated = Transaction::withoutGlobalScopes()->where('recurring_source_id', $source->id)->pluck('id');
    $activities = Activity::query()->where('event', 'created')->whereIn('subject_id', $generated)->get();

    expect($generated)->not->toBeEmpty()
        ->and($activities)->toHaveCount($generated->count())
        ->and($activities->pluck('causer_id')->unique()->all())->toBe([$user->id])
        ->and($activities->map(fn (Activity $activity) => $activity->getProperty('via'))->unique()->all())->toBe(['system']);

    Carbon::setTestNow();
});

it('shows only the authenticated user activity on the account activity page', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($other)->post(route('wallets.store'), [
        'name' => 'Carteira Secreta do Outro', 'type' => 'checking', 'ownership' => 'personal',
    ])->assertRedirect();

    $this->actingAs($owner)->post(route('wallets.store'), [
        'name' => 'Carteira do Dono', 'type' => 'checking', 'ownership' => 'personal',
    ])->assertRedirect();

    $this->actingAs($owner)
        ->get(route('settings.activity', ['user_id' => $other->id, 'causer_id' => $other->id]))
        ->assertOk()
        ->assertSee('Atividade da conta')
        ->assertSee('Carteira do Dono')
        ->assertSee('Criou')
        ->assertDontSee('Carteira Secreta do Outro');

    $this->actingAs($other)
        ->get(route('settings.activity'))
        ->assertOk()
        ->assertSee('Carteira Secreta do Outro')
        ->assertDontSee('Carteira do Dono');
});

it('shows wallet and category names instead of ids on the activity page', function () {
    $user = User::factory()->create();
    $walletA = Wallet::factory()->for($user)->create(['name' => 'Carteira Antiga', 'type' => 'checking', 'balance' => 1000]);
    $walletB = Wallet::factory()->for($user)->create(['name' => 'Carteira Nova', 'type' => 'checking', 'balance' => 1000]);
    $categoryA = Category::factory()->for($user)->create(['name' => 'Mercado', 'type' => 'expense']);
    $categoryB = Category::factory()->for($user)->create(['name' => 'Lazer', 'type' => 'expense']);

    $this->actingAs($user)->post(route('transactions.store'), [
        'wallet_id' => $walletA->id,
        'category_id' => $categoryA->id,
        'type' => 'expense',
        'amount' => '50.00',
        'description' => 'Compra',
        'date' => now()->toDateString(),
    ])->assertRedirect();

    $transaction = Transaction::firstOrFail();

    $this->actingAs($user)->put(route('transactions.update', $transaction), [
        'wallet_id' => $walletB->id,
        'category_id' => $categoryB->id,
        '_has_recurring_field' => 1,
    ])->assertRedirect();

    $walletA->delete();

    $this->actingAs($user)->get(route('settings.activity'))
        ->assertOk()
        ->assertSee('Carteira Antiga (excluída)')
        ->assertSee('Carteira Nova')
        ->assertSee('Mercado')
        ->assertSee('Lazer')
        ->assertDontSee($walletA->id)
        ->assertDontSee($walletB->id)
        ->assertDontSee($categoryA->id)
        ->assertDontSee($categoryB->id);
});

it('never resolves another user wallet id to its name on the activity page', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['name' => 'Minha Carteira', 'type' => 'checking', 'balance' => 1000]);
    $foreign = Wallet::factory()->for($other)->create(['name' => 'Carteira Secreta Alheia', 'type' => 'checking']);

    $this->actingAs($user)->post(route('transactions.store'), [
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => '50.00',
        'description' => 'Compra',
        'date' => now()->toDateString(),
    ])->assertRedirect();

    // Simulate a log row that points to another user's wallet.
    $activity = Activity::query()->where('event', 'created')
        ->where('subject_type', (new Transaction)->getMorphClass())
        ->firstOrFail();
    $activity->attribute_changes = ['attributes' => ['wallet_id' => $foreign->id]];
    $activity->save();

    $this->actingAs($user)->get(route('settings.activity'))
        ->assertOk()
        ->assertSee('(excluída)')
        ->assertDontSee('Carteira Secreta Alheia')
        ->assertDontSee($foreign->id);
});

it('shows failed logins against the account on its activity page', function () {
    $user = User::factory()->create();

    $this->post('/login', ['username' => $user->username, 'email' => $user->email, 'password' => 'errada-123']);

    $this->actingAs($user)->get(route('settings.activity'))
        ->assertOk()
        ->assertSee('Tentativa de acesso sem sucesso');
});

it('shows the empty state when there is no activity', function () {
    $user = User::factory()->create();
    Activity::query()->delete();

    $this->actingAs($user)->get(route('settings.activity'))
        ->assertOk()
        ->assertSee('Nenhuma atividade registrada');
});

it('redirects guests from the account activity page to login', function () {
    $this->get(route('settings.activity'))->assertRedirect(route('login'));
});
