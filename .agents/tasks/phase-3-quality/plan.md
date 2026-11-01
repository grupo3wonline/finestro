# Implementation Plan: Phase 3 (quality tooling + Phase 2 billing gaps)

Project root (work in place, no worktree, no commits): `d:\laragon\www\3wonline\0\0-dev\sys\homol\Finestro`.
FEAT artifacts: `.agents\tasks\phase-3-quality\task-phase-3-quality\` (task.json, context.json, features/FEAT-001..005.json). This plan and the FEAT files describe the same work.

## Design decisions

- **Versions (checked with `composer show -a`).** Installed: laravel/framework 13.9.0, phpunit 12.5.25, collision 8.9.4, pint 1.29.1, PHP 8.5.6. Pest 5 needs PHPUnit 13 and pest-plugin-laravel 5 needs framework ^13.23, so use **Pest ^4.7 + pest-plugin-laravel ^4.1** (Pest 4.7.8 needs phpunit ^12.5.33 and collision ^8.9.5; bump those two inside their current majors). **larastan/larastan ^3.13**, **rector/rector ^2.8**, **driftingly/rector-laravel ^2.7** (all support Laravel 13 / PHPStan 2).
- **Pest coexistence.** Write `tests/Pest.php` by hand (do not run `pest --init`, it touches example tests). `pest()->extend(Tests\TestCase::class)->use(RefreshDatabase::class)->in('Feature')` only affects closure-based files; class-based tests keep their own `use RefreshDatabase`.
- **Rector sets.** `withPreparedSets(deadCode, codeQuality, typeDeclarations)` + `LaravelSetList::LARAVEL_130_WITHOUT_ATTRIBUTES` (the 13.0 set without converting `$fillable`/`$casts` properties into PHP attributes, which is a large non-conservative rewrite). PHP level set: **not php85**. composer.json declares `"php": "^8.3"` and the deploy target (HostGator FTP, `.github/workflows/deploy.yml`) has an unverified PHP version; PHP 8.4/8.5-only syntax would stop the app running there, which is a behaviour change. Use `withPhpSets()` with no arguments (derives PHP 8.3 from composer.json) and skip `ReadOnlyPropertyRector` / `ReadOnlyClassRector`. Document this; raising the PHP floor is a user decision (deferred).
- **Rector/PHPStan scope.** Rector: app, config, database (skip database/migrations), routes. Not tests (the brief keeps existing tests as they are). PHPStan: app, config, database, routes at level 6 with Larastan.
- **Pint scope.** pint.json preset laravel; exclude vendor, storage, bootstrap/cache, node_modules, public; `notName: ["*.blade.php"]`. Pint runs over app, config, database, routes, tests, bootstrap/app.php (formatting only).
- **B1 success page.** The controller already redirects via `Subscription::ineligibleAccessMessage()` for any non-Pro result; the doc text in the brief is stale. Make the condition explicit (`! $subscription->isProfessional()`), keep message selection in the model, and reword the past_due/unpaid and incomplete messages to start with "Pagamento pendente." / "Pagamento ainda não confirmado." as the brief asks.
- **B2 past_due cancel.** One route (`subscription.cancel`). `SubscriptionService::cancel(User)` routes past_due/unpaid subscriptions to a new immediate cancellation (gateway `cancelImmediately` = Stripe `subscriptions->cancel`), everything else to the existing `cancelAtPeriodEnd`. Immediate because there is no paid period to keep; local row becomes `canceled`, `current_period_end = ended_at ?? now()`, same as the `customer.subscription.deleted` webhook (idempotent if the webhook arrives later). Unpaid is included for the same reason.
- **B3 account deletion.** New `AccountDeletionService` (+ interface, + `AccountDeletionException`) used by both `SettingController::deleteAccount` and Breeze `ProfileController::destroy`. It cancels an open Stripe subscription through `SubscriptionServiceInterface::cancelImmediately()` (reused from B2, DRY), then soft-deletes the user. On any gateway failure it logs (`Log::error`, exception class only, never keys) and throws; controllers show the pt-BR error and keep the user logged in. "Open in Stripe" = has `stripe_subscription_id` and status not in {canceled, incomplete_expired} (new `Subscription::isOpenInStripe()`).
- **B4 recurring cap.** Add `User::transactionCountInMonth(CarbonInterface)`; `canCreate('transaction')` reuses it. The command keeps an in-memory per-user-per-month counter seeded from that method, reads the cap from `$user->plan_limits['transactions_per_month']`, skips occurrences beyond it (logs `Log::info` + console `warn`), and continues with later dates (next month may have room). Pro users have `PHP_INT_MAX`, so unlimited. When the series' user is missing (soft-deleted), keep current behaviour (no cap check).
- **B5 plan.limit.** Search found one reference: `tests/Feature/Billing/PlanGatingTest.php::test_plan_limit_middleware_message_uses_configured_price` registers a probe route with `plan.limit:feature,simulators`. No production route or view uses it. Because the brief keeps existing tests as they are and says to keep the middleware when something references it, **keep `CheckPlanLimit` and its alias**, and correct the billing doc (it currently says limits are "enforced in ... the plan.limit middleware").

## Ordered items

- [ ] 1. **Install Pest and add tests/Pest.php** (FEAT-001).
      `composer require --dev "pestphp/pest:^4.7" "pestphp/pest-plugin-laravel:^4.1" "phpunit/phpunit:^12.5.33" "nunomaduro/collision:^8.9.5" --with-dependencies`; confirm laravel/framework stays 13.x. Create `tests/Pest.php` with the Feature extend/use line.
      Files: composer.json, composer.lock, tests/Pest.php
      Verify: `php artisan test` → 306 passed / 952 assertions; `php vendor/bin/pest` → same counts.

- [ ] 2. **Billing gaps B1–B5 with Pest tests + billing doc** (FEAT-002, needs 1).
      a. Gateway: add `cancelImmediately(string $stripeSubscriptionId): array` to `StripeGatewayInterface` (same array shape doc as `cancelAtPeriodEnd`), implement in `StripeGateway` (`$this->client()->subscriptions->cancel($id)` → `StripePayloadMapper::subscriptionData`), and in `tests/Fakes/FakeStripeGateway` (records call; throws `public ?\Throwable $cancelException` when set; returns status `canceled`, `ended_at` now).
      b. Model: `Subscription::isOpenInStripe(): bool`; reword the past_due/unpaid and incomplete messages in `ineligibleAccessMessage()`.
      c. Service: add `cancel(User): Subscription` and `cancelImmediately(Subscription): Subscription` to `SubscriptionServiceInterface` and `SubscriptionService`.
      d. Controller: `SubscriptionController::success` uses `if (! $subscription->isProfessional())` + `ineligibleAccessMessage()`; `cancel()` calls `$this->service->cancel()` and flashes "Assinatura cancelada. O Stripe não fará novas tentativas de cobrança e sua conta segue no plano Básico." when the result is canceled, else the existing scheduled message.
      e. View `resources/views/subscription/index.blade.php`: add one `@elseif` branch before the final `@else` in "Gestão do plano" for past_due/unpaid with a `subscription.cancel` form using `data-confirm` (SweetAlert2 hook) and `<x-ui.button variant="outline" icon="fa-ban">`. Targeted edit only.
      f. Account deletion: `App\Exceptions\AccountDeletionException`, `App\Interfaces\Services\AccountDeletionServiceInterface`, `App\Services\AccountDeletionService`, binding in `AppServiceProvider`; use it in `SettingController::deleteAccount` and `ProfileController::destroy` (service call before `Auth::logout()`; on exception redirect to settings.index / profile.edit with `error`).
      g. Recurring cap: `User::transactionCountInMonth()`, refactor `canCreate('transaction')`, cap logic in `ProcessRecurringTransactions::handle` (eager-load `user.subscription`).
      h. B5: keep `CheckPlanLimit`; document why.
      i. Pest tests: `tests/Feature/Billing/CheckoutSuccessStatusTest.php`, `tests/Feature/Billing/PastDueCancelTest.php`, `tests/Feature/AccountDeletionTest.php`, `tests/Feature/RecurringPlanCapTest.php`.
      j. Update `docs/billing/phase-2-stripe.md` (plans intro sentence, Plan management cancel bullets, Known gaps → fixed/remaining).
      Files: listed above
      Verify: `php vendor/bin/pest tests/Feature/Billing tests/Feature/AccountDeletionTest.php tests/Feature/RecurringPlanCapTest.php tests/Feature/ProfileTest.php tests/Feature/BudgetInvoiceRecurringTest.php` pass; then `php artisan test` all pass; `php artisan view:cache` then `php artisan view:clear` succeed.

- [ ] 3. **Rector + Pint** (FEAT-003, needs 2).
      `composer require --dev "rector/rector:^2.8" "driftingly/rector-laravel:^2.7"`. Write `rector.php` and `pint.json` per the decisions. `php vendor/bin/rector --dry-run` → review the diff for behaviour changes (skip any offending rule via `withSkip` and record it), apply, `php vendor/bin/pint`, tests.
      Files: rector.php, pint.json, composer.json/lock, PHP files rewritten by the tools
      Verify: `php vendor/bin/rector --dry-run` reports no changes; `php vendor/bin/pint --test` passes; `php artisan test` passes; `git status --porcelain resources/views` shows no new Blade changes from Pint.

- [ ] 4. **PHPStan + Larastan level 6** (FEAT-004, needs 3).
      `composer require --dev "larastan/larastan:^3.13"`; write `phpstan.neon`; fix findings in code (relation generics `HasMany<Model, $this>`, array value types, return types, null-safety, dead code). Baseline `phpstan-baseline.neon` only for framework magic that cannot be fixed cleanly. Re-run Rector dry-run and Pint after fixes.
      Files: phpstan.neon, optional phpstan-baseline.neon, app/**, config/**, database/**, routes/**
      Verify: `php vendor/bin/phpstan analyse --memory-limit=1G` → "No errors"; rector dry-run clean; pint --test clean; `php artisan test` passes.

- [ ] 5. **Composer scripts, CI workflow, docs, full DONE verification** (FEAT-005, needs 4).
      Scripts `lint`, `format`, `analyse`, `refactor`, `test` (keep existing config:clear + test), `quality`. New `.github/workflows/quality.yml` (deploy.yml untouched). New `docs/quality/phase-3-tooling.md`.
      Files: composer.json, .github/workflows/quality.yml, docs/quality/phase-3-tooling.md
      Verify: `composer quality` exits 0; `php artisan test`; `php artisan test --order-by=random`; `php vendor/bin/pest`; `php artisan route:list`; `php artisan view:cache` then `php artisan view:clear`; `npm run build`. Run each test command alone.

## Digest

- **composer.json**: require-dev has pint ^1.27, phpunit ^12.5.12, collision ^8.6, no Pest/PHPStan/Rector. `config.allow-plugins` already allows `pestphp/pest-plugin`. Scripts: `setup`, `dev`, `test` = [`@php artisan config:clear --ansi @no_additional_args`, `@php artisan test`], post-* hooks. `"php": "^8.3"`.
- **phpunit.xml**: suites Unit + Feature, SQLite `:memory:`, forced empty Stripe keys, `STRIPE_PROFESSIONAL_PRICE_ID=price_test_professional`.
- **tests/TestCase.php**: `$seed = true`, `$seeder = RolesAndPermissionsSeeder::class`, `withoutVite()` in setUp.
- **app/Http/Controllers/SubscriptionController.php**: `success()` already does `if ($message = $subscription->ineligibleAccessMessage()) redirect(subscription.index)->with('error', $message)`; `cancel()` calls `$this->service->cancelAtPeriodEnd(Auth::user())`, catches `SubscriptionActionException` (error flash) and `\Throwable` (log + generic error). Covered by `tests/Feature/Billing/CheckoutSuccessTest.php`, `PlanManagementTest.php`.
- **app/Models/Subscription.php**: `isActive()`, `isProfessional()` (`plan === 'professional' && isActive()`), `isExpired()`, `hasPendingCancellation()`, `ineligibleAccessMessage(): ?string` (match: expired → "Sua assinatura expirou…", PAST_DUE/UNPAID → "Há um pagamento pendente…", INCOMPLETE/INCOMPLETE_EXPIRED → "O pagamento ainda não foi concluído…", default → "Esta assinatura já foi encerrada…"). Status cast to `App\Enums\SubscriptionStatus`.
- **app/Services/SubscriptionService.php**: constructor (gateway, subscriptions repo, payments repo, pricing, webhookEvents). `cancelAtPeriodEnd(User)` requires `isProfessional()`, throws `SubscriptionActionException` otherwise; `remoteAttributes()` private helper; `onSubscriptionDeleted()` sets status CANCELED, `canceled_at`, `cancel_at_period_end=false`, `current_period_end = ended_at ?? now()`.
- **app/Interfaces/Services/StripeGatewayInterface.php**: `ensureCustomer`, `createCheckoutSession`, `retrieveCheckoutSession`, `cancelAtPeriodEnd`, `resumeSubscription`, `constructWebhookEvent`. **app/Services/StripeGateway.php** lines ~120-136: `cancelAtPeriodEnd`/`resumeSubscription` call `$this->client()->subscriptions->update(...)` and return `StripePayloadMapper::subscriptionData($subscription)`.
- **tests/Fakes/FakeStripeGateway.php**: public `$sessions`, `$retrieveException`, `$checkoutUrl`, `$calls`; private `subscriptionResponse($id, bool)`.
- **app/Interfaces/Repositories/SubscriptionRepositoryInterface.php**: `findLatestForUser`, `findByStripeId`, `updateOrCreateForUser`, `updateAttributes(Subscription, array)`.
- **app/Providers/AppServiceProvider.php** lines 62-86: interface → implementation `bind` calls.
- **app/Http/Controllers/SettingController.php** `deleteAccount(Request)`: validate password (`current_password`), `Auth::logout()`, `$user->delete()`, invalidate, regenerate, redirect `home` with success. Route `settings.account.delete` (DELETE /configuracoes/conta). No test covers it today.
- **app/Http/Controllers/ProfileController.php** `destroy(Request)`: `validateWithBag('userDeletion', ...)`, logout, delete, invalidate, redirect `/`. Covered by `tests/Feature/ProfileTest.php::test_user_can_delete_their_account` (asserts guest + soft deleted).
- **app/Models/User.php**: SoftDeletes, HasRoles, HasUuid. `subscription()` hasOne latestOfMany; `getPlanLimitsAttribute()` (Pro: PHP_INT_MAX; Free: `transactions_per_month` 100); `canCreate('transaction')` counts `transactions()` in `now()` month/year.
- **app/Console/Commands/ProcessRecurringTransactions.php**: `handle(TransactionServiceInterface)`; query `Transaction::withoutGlobalScopes()->whereNull('deleted_at')...->get()->groupBy(...)`; inner `while ($next->lte($limit) && $generated < self::MAX_PER_SERIES)` prints a line, creates via `createTransaction(TransactionDTO::fromArray([...]))` unless dry-run, increments `$created`, `$generated`, `$k`. Covered by `tests/Feature/BudgetInvoiceRecurringTest.php` (recurring tests at ~line 195-240, use `Carbon::setTestNow`).
- **app/Http/Middleware/CheckPlanLimit.php** + alias `'plan.limit'` in `bootstrap/app.php` line 21; only reference outside docs: `tests/Feature/Billing/PlanGatingTest.php` ~line 244.
- **resources/views/subscription/index.blade.php** "Gestão do plano" section (~line 136-189): `@if($pendingCancellation)` resume form; `@elseif($isPro)` cancel form with `data-confirm="..."`; `@else` checkout form. Past-due alert at ~line 41 (`$subscription->status === \App\Enums\SubscriptionStatus::PAST_DUE`).
- **database/factories/SubscriptionFactory.php** states: `free`, `canceled`, `pendingCancellation`, `expired`, `pastDue`.
- **.github/workflows/deploy.yml**: FTP deploy, must not be modified.
- **docs/billing/phase-2-stripe.md**: "Known gaps (deferred)" lists 5 bullets (account deletion, past_due cancel, recurring cap, latestOfMany, plan.limit unused); line 7 claims plan.limit enforces limits.
- Baseline (from brief, not re-run during planning): `php artisan test` = 306 passed / 952 assertions.
