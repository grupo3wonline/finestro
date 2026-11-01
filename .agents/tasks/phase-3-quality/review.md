# Quality tooling (Pest, PHPStan/Larastan, Pint, Rector) and Phase 2 billing gap fixes

Phase 3 adds Pest 4 next to the existing PHPUnit classes, PHPStan level 6 with Larastan, Pint (laravel preset, Blade excluded) and Rector with conservative sets, all wired into `composer quality` and a new `.github/workflows/quality.yml`. It also closes the four Phase 2 billing gaps. The success page now redirects every non-Pro result with a per-status message. A past_due/unpaid subscriber can cancel immediately from Financeiro. Account deletion goes through a new `AccountDeletionService` that cancels Stripe first and blocks on failure. The recurring command respects the Free monthly cap from `plan_limits`. The plan.limit middleware is kept and the reason documented. The FEAT evidence records `composer quality` exit 0, 330 tests / 1046 assertions in both `php artisan test` runs (the second with `--order-by=random`) and in `vendor/bin/pest`, plus a 3-entry baseline.

Watch for: the billing doc's promise that cap-skipped recurring dates are generated after an upgrade only holds until a later occurrence is created (confirmed). Account deletion stays blocked for good if Stripe no longer knows the subscription id (likely). The FEAT-002 record of what happens to series whose owner is missing does not match the code (confirmed). The tooling doc's PHP 8.3 deploy-target rationale is already untrue for the current lock file (likely).

**Verdict**: APPROVED

## High-level view

The tooling is set up as briefed. `tests/Pest.php` applies `TestCase` and `RefreshDatabase` only to closure tests under `tests/Feature`, so the class-based tests run unchanged. Rector covers app, config, database (minus migrations) and routes with deadCode, codeQuality, typeDeclarations, `LARAVEL_130_WITHOUT_ATTRIBUTES` and a composer-derived PHP set, and skips the readonly rules. The rewrites I checked are behaviour-preserving in reachable paths. `strict_types` landed mostly on interfaces, config and `Goal`/`GoalService`, where the arithmetic is unaffected. The closure return types match the return types of the methods that enclose them. The baseline has three entries, all the same `Filterable` `?? []` fallback reported once per model, and it is documented in both `phpstan.neon` and the tooling doc. No inline `@phpstan-ignore` exists.

On billing, the cancel path is routed in `SubscriptionService::cancel()`: past_due/unpaid goes to `cancelImmediately()` and everything else to `cancelAtPeriodEnd()`. Account deletion reuses `cancelImmediately()` and fails closed on any gateway error. The recurring cap is computed per user and month in memory, seeded from `User::transactionCountInMonth()`, which `canCreate('transaction')` also uses. The four new Pest files cover each Part B item: every success-page status, the past_due form plus immediate cancel and gateway failure, both deletion endpoints for success, failure, Free and already-canceled, and the Free cap against Pro. `incomplete_expired` deliberately gets the "Assine novamente" copy because `StripePayloadMapper` maps it to canceled, and the doc records this. The remaining concerns are edge cases in the deletion and recurring paths, plus documentation accuracy.

`deploy.yml` was last written at 18:23, before the Phase 3 plan (21:58). Its `M` status comes from earlier phases. The only Blade file touched in Phase 3 is `subscription/index.blade.php`, a single `@elseif` branch with `data-confirm`. No migration was touched. No Stripe key appears in code, docs or tests.

<details>
<summary>Issues (4)</summary>

1. **Recurring "retry after upgrade" overpromise** — `docs/billing/phase-2-stripe.md` says skipped dates are generated if the user upgrades. They are lost as soon as any later occurrence is created, because the command resumes after `$last->date`. Reword the doc, or track skipped dates if retrying them is the intended behaviour.
2. **Deletion blocked when Stripe no longer has the subscription** — `AccountDeletionService` treats every gateway exception as fatal. A stale local row (subscription or customer deleted in Stripe, missed webhook) blocks deletion until support steps in. Consider treating Stripe `resource_missing` as "already canceled" and adding a test for it.
3. **Missing-owner series behaviour misrecorded** — FEAT-002 says a series whose user is null "keeps old behaviour (no cap)". The command actually `continue`s and skips the series entirely. Correct the record or the code, and add a test for a soft-deleted owner.
4. **PHP 8.3 deploy-target rationale** — `docs/quality/phase-3-tooling.md` justifies not using php85 because the deploy target "only has to support 8.3". The lock file already required PHP ≥ 8.4 before Phase 3 (symfony 8.x). The conservative choice is fine; fix the stated reason.

</details>

<details>
<summary>Details</summary>

### Recurring cap and the retry promise

`ProcessRecurringTransactions` reads `$last->user->plan_limits['transactions_per_month']`, treats `PHP_INT_MAX` as unlimited, and keeps an in-memory `monthlyCounts[user][Y-m]` seeded lazily from `transactionCountInMonth()`. It resets the counts per `handle()` because Artisan reuses the command instance. Skipped occurrences are logged with `Log::info` (user id, series id, date, limit, no PII beyond ids) and printed as `[limite]`. `RecurringPlanCapTest` covers both sides: a Free user at 99 March rows gets exactly one occurrence plus a skip log, and a Pro user gets all nine.

The billing doc adds: "Skipped dates are retried on later runs, so they are generated if the user upgrades" (**confirmed** partially wrong). The loop starts at the first occurrence after `$last->date`:

```
while ($this->occurrence($anchor, $k, ...)->lte($last->date)) { $k++; }
```

Suppose a run skips 10–31 March and then, still inside the 7-day horizon or on the first April run, creates 1 April. From then on `$last->date` is 1 April and the March dates are never generated. Retrying only works while no later occurrence exists. Each scheduler run also re-logs every still-skipped date, so a capped daily series writes one log line per skipped day on every run until the month changes. That is noise but harmless.

The series filter also skips owners that cannot be loaded (`$last->user === null`). The eager load drops soft-deleted users, so their series stop generating (**confirmed** in code). That is a defensible outcome for deleted accounts. FEAT-002's findings, however, say the null-user case "keeps old behaviour (no cap)", which describes different code. No test pins either behaviour.

### Account deletion fails closed on every gateway error

`AccountDeletionService::delete()` cancels through `cancelImmediately()` whenever `isOpenInStripe()` is true. On any `\Throwable` it logs only `user_id`, `subscription_id` and the exception class, then throws a pt-BR `AccountDeletionException`. Fail-closed is what the brief asks for. The open case is a local row that is stale (**likely**). If the subscription or customer was deleted in the Stripe Dashboard, or the `customer.subscription.deleted` webhook was lost, `subscriptions->cancel()` raises `InvalidRequestException` (resource_missing) on every attempt. The user can then never delete their own account, which matters for the LGPD deletion right. Mapping resource_missing to "already canceled" (and updating the local row) would keep the block for real outages only.

### Documentation accuracy

`docs/quality/phase-3-tooling.md` explains `withPhpSets()` without php85 because "the deploy target only has to support 8.3" (**likely** inaccurate). `composer.lock` at HEAD already pinned `symfony/http-kernel` and `symfony/console` v8.0.11, and Symfony 8 requires PHP ≥ 8.4. The real floor is therefore already 8.4. Keeping Rector at 8.3-level syntax is still a valid conservative choice; the stated reason should say so.

### Provenance notes

FEAT-003/004 record edits made by another process while the steps ran: `tests/Feature/SubscriptionServiceTest.php` (22:15, +3 tests), `app/Traits/Filterable.php` (restored at 22:52) and `app/Repositories/GoalRepository.php` (22:49, `update()` now uses `findOrFail`). The `GoalRepository` change turns a missing id from an `Error` on null into a 404. Controllers already resolve and authorize the goal first, so no reachable path changes. The final FEAT-005 runs came after all three timestamps, so the recorded green state includes them.

</details>

<details>
<summary>File map</summary>

Tooling: `composer.json`/`composer.lock` (Pest 4.7, pest-plugin-laravel 4.1, PHPUnit 12.5.33, collision 8.9.5, Larastan 3.13, Rector 2.8, rector-laravel 2.7; lint/format/analyse/refactor/quality scripts), `tests/Pest.php`, `phpstan.neon`, `phpstan-baseline.neon` (3 entries), `pint.json`, `rector.php`, `.github/workflows/quality.yml` (new), `docs/quality/phase-3-tooling.md` (new).

Billing: `app/Interfaces/Services/StripeGatewayInterface.php` + `app/Services/StripeGateway.php` + `tests/Fakes/FakeStripeGateway.php` (`cancelImmediately`), `app/Models/Subscription.php` (`isOpenInStripe`, `isPaymentPending`, reworded messages), `app/Interfaces/Services/SubscriptionServiceInterface.php` + `app/Services/SubscriptionService.php` (`cancel`, `cancelImmediately`), `app/Http/Controllers/SubscriptionController.php`, `resources/views/subscription/index.blade.php` (one past_due branch), `app/Exceptions/AccountDeletionException.php`, `app/Interfaces/Services/AccountDeletionServiceInterface.php`, `app/Services/AccountDeletionService.php`, `app/Providers/AppServiceProvider.php` (binding), `app/Http/Controllers/SettingController.php`, `app/Http/Controllers/ProfileController.php`, `app/Models/User.php` (`transactionCountInMonth`), `app/Console/Commands/ProcessRecurringTransactions.php` (cap), `docs/billing/phase-2-stripe.md`.

New Pest tests: `tests/Feature/Billing/CheckoutSuccessStatusTest.php`, `tests/Feature/Billing/PastDueCancelTest.php`, `tests/Feature/AccountDeletionTest.php`, `tests/Feature/RecurringPlanCapTest.php`.

Tool rewrites: about 130 PHP files in app, config, database/factories, database/seeders, routes, `bootstrap/app.php` and two tests (Rector rules plus Pint style; dry-run diff in `.agents/tasks/phase-3-quality/rector-dry-run.txt`) and PHPStan docblock/generic fixes across models, repositories, services, DTOs and Form Requests.

Full diff: `git diff` in the project root (the tree also holds uncommitted Phase 1/2 work).

</details>
