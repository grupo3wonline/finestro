# Phase 6 verification log

## FEAT-001 – Phase 5 findings (IP limiter, Scout subquery, wallet update contract)

Run on 2026-10-31 (last day of the month; see note on date-dependent failures). All commands run alone, cwd = project root.

| # | Command | Exit | Result |
|---|---|---|---|
| 1 | `php artisan test --filter="ApiIpRateLimit\|ApiFoundation\|ApiRouteRegistry\|ApiEndpointMatrix"` (first run) | 1 | 3 failed: IP limiter sorted after `Authenticate:sanctum` (plan decision 16 premise wrong). Fixed with `prependToPriorityList(AuthenticatesRequests::class, ThrottleApiByIp::class)` in bootstrap/app.php |
| 2 | same filter (rerun) | 0 | 185 passed, 15 skipped (851 assertions) |
| 3 | `php artisan test --filter="TransactionSearch\|TransactionApi\|TransactionLifecycle\|ReportExport"` (first run) | 1 | `Laravel\Scout\Builder::engine()` is protected; switched to `$scout->model->searchableUsing()` |
| 4 | same filter (rerun) | 1 | 32 passed, 9 failed (190 assertions). New 1 500-match subquery test passes. The 9 failures are date-dependent (see note) |
| 5 | `php artisan test --filter="WalletApi\|WalletUpdate\|WalletAccess\|OpenApiSpec"` | 0 | 37 passed (421 assertions) |
| 6 | `php artisan l5-swagger:generate` | 0 | Regenerating docs default |
| 7 | `composer analyse` (first run) | 1 | 1 error: Scout Builder template not covariant in `SubqueryDatabaseEngine::searchQuery()`; fixed with `@template TModel` |
| 8 | `composer analyse` (rerun) | 0 | [OK] No errors (baseline unchanged) |
| 9 | `php artisan test` | 1 | 681 passed, 16 failed, 15 skipped (2942 assertions), 46.4 s |
| 10 | `vendor/bin/pint --test` on the 15 changed PHP files | 0 | PASS |

### Date-dependent failures (pre-existing, not caused by FEAT-001)

All 16 failures in run 9 (and the 9 in run 4) come from `Transaction::scopeInMonth()`:
`whereBetween('date', ['2026-10-01', '2026-10-31'])` against SQLite values stored as `2026-10-31 00:00:00`. The string comparison excludes the last day of the month, so rows dated "today" disappear from month-filtered lists and summaries on the 31st. A debug run confirmed the month filter alone (no search) returns no rows for a transaction dated today.

Affected: TransactionApiTest (1), ReportExportTest (4), TransactionSearchTest (4 existing tests that use the default month filter), BudgetInvoiceRecurringTest (3), DashboardRendersTest (1), UserCacheTest (3). They should pass on any other day. MySQL `DATE` columns are not affected. Fixing the scope is outside FEAT-001's scope.

## FEAT-002 – Filament v5.11 install, admin guard, MFA, branding, suspension, commands

Run 2026-11-01 00:xx UTC = 2026-10-31 21:xx America/Sao_Paulo (app timezone), i.e. still the last day of the month in app time. All commands run alone, cwd = project root.

| # | Command | Exit | Result |
|---|---|---|---|
| 1 | `composer require "filament/filament:^5.11" --no-interaction` | 0 | Installed filament/* v5.11.0, livewire/livewire v4.4.8, pragmarx/google2fa v9.1.0 (+ deps); 0 updates/removals; "Found 41 security vulnerability advisories affecting 11 packages" (pre-existing). Windows `composer.bat` ate the `^`, so composer.json got `"5.11"`; corrected by hand to `"^5.11"` |
| 2 | `composer update --lock --dry-run --no-scripts`, then `composer update --lock` | 0 / 0 | "Nothing to modify in lock file" / lock hash refreshed, no package changes |
| 3 | `composer show filament/filament` / `livewire/livewire` / `laravel/framework` / `pragmarx/google2fa` | 0 | v5.11.0 / v4.4.8 / **v13.9.0 (unchanged)** / v9.1.0 |
| 4 | `php artisan filament:install --panels --no-interaction` | 0 | Created app/Providers/Filament/AdminPanelProvider.php, registered in bootstrap/providers.php, published public/{css,js,fonts}/filament, added those 3 paths to .gitignore, added `@php artisan filament:upgrade` to post-autoload-dump (kept) |
| 5 | SHA-256 of package.json, vite.config.js, tailwind.config.js, resources/css/app.css before/after install and after build | – | identical (56CFEB2D…, 5EA7A0D0…, A784D36B…, AD3687DA…) |
| 6 | `php artisan migrate --pretend` | 0 | only: `alter table users add suspended_at timestamp null`, `add app_authentication_secret text null`, `add app_authentication_recovery_codes text null`, `add index users_suspended_at_index` |
| 7 | `php artisan migrate --no-interaction` (dev MySQL) | 0 | 2026_11_15_000100_add_suspension_and_mfa_to_users_table DONE |
| 8 | `php artisan test --filter="AdminAccess\|AdminLogin\|AdminCsp\|Suspension\|AdminCommands"` (first run) | 2 | 6 failed: test helper return type resolved to `Livewire\Livewire\...\Testable` (missing `use`); fixed import |
| 9 | same filter (rerun) | 0 | 31 passed (119 assertions) |
| 10 | `php artisan test --filter="Authentication\|RolePermission\|ApiFoundation\|ApiEndpointMatrix\|GuestAccess"` | 0 | 245 passed, 15 skipped (855 assertions) |
| 11 | `php artisan filament:optimize` | 0 | Caching components / Blade icons DONE |
| 12 | `php artisan filament:optimize-clear` | 0 | cleared |
| 13 | `php artisan view:cache` | 0 | Blade templates cached successfully |
| 14 | `php artisan view:clear` | 0 | Compiled views cleared |
| 15 | `npm run build` | 0 | built in 3.1 s (app css 142.96 kB, js 229.12 kB) |
| 16 | `composer analyse` | 0 | [OK] No errors (baseline unchanged, 3 entries) |
| 17 | `vendor/bin/pint --test` on the 18 changed/new PHP files | 0 | PASS |
| 18 | `vendor/bin/rector --dry-run` (first run) | 2 | 3 files: MakeAdmin/ResetAdminMfa missing `declare(strict_types=1)` (fixed) + app/OpenApi/RequestSchemas.php:45 named-arg order (FEAT-001 file, `SortCallLikeNamedArgsRector`; not touched here) |
| 19 | `php artisan test` | 1 | 712 passed, 16 failed, 15 skipped (3061 assertions), 58.3 s. The 16 failures are exactly FEAT-001's date-dependent set (TransactionApi 1, ReportExport 4, TransactionSearch 4, BudgetInvoiceRecurring 3, DashboardRenders 1, UserCache 3) |
| 20 | `php artisan test --filter=AdminCommands` (after strict_types) | 0 | 7 passed (22 assertions) |
| 21 | `vendor/bin/rector --dry-run` (rerun) | 1 | only app/OpenApi/RequestSchemas.php:45 remains (FEAT-001) |
| 22 | `php artisan route:list --path=admin` | 0 | 4 routes: admin (dashboard), admin/login, admin/logout, admin/multi-factor-authentication/set-up |
| 23 | `php artisan list finestro` | 0 | finestro:admin-mfa-reset, finestro:make-admin |

CSP: AdminCspTest found no violation on /admin/login (Filament assets self-hosted, Google font stylesheet on fonts.googleapis.com, fonts on fonts.gstatic.com); app/Http/Middleware/SecurityHeaders.php unchanged.

## FEAT-003 – Admin services/repositories, Filament resources, widgets, export, tests

Run 2026-10-31 ~22:00–22:25 America/Sao_Paulo (2026-11-01 ~01:00–01:25 UTC). All commands run alone, cwd = project root.

| # | Command | Exit | Result |
|---|---|---|---|
| 1 | `php artisan test --filter="ActivityLog\|AccountDeletion\|AdminUserService\|AdminMetricsService"` (items 11-12) | 0 | 54 passed (229 assertions) |
| 2 | `php artisan make:filament-resource User --view --no-interaction` | 0 | Scaffold generated; CreateUser/EditUser/UserForm deleted (read-only resource) |
| 3 | `php artisan route:list --path=admin` (after resources) | 0 | 10 routes: dashboard, users (+view), subscriptions, payment-histories, activities (+view), login, logout, MFA set-up |
| 4 | `php artisan test --filter="UserResource\|UserActions\|BillingResources\|ActivityResource\|DashboardWidgets\|DataMinimisation\|LivewireAuthorization"` (first run) | 1 | 45 passed, 2 failed: `Panel::isGlobalSearchEnabled()` does not exist (use `getGlobalSearchProvider()`); Livewire v4 snapshot memo name is the component class name, not a kebab alias. Both test-side fixes |
| 5 | `php artisan test --filter="DataMinimisation\|LivewireAuthorization"` (rerun) | 0 | 11 passed (63 assertions) |
| 6 | `vendor/bin/pint` on the new/changed files | 0 | 61 files PASS |
| 7 | `vendor/bin/rector --dry-run` (first run) | 1 | 4 files: generated CreateUser/EditUser (leftover scaffold, deleted), app/OpenApi/RequestSchemas.php:45 (FEAT-001 named-arg order, applied by hand), AdminUserRepository static label() → instance method (applied) |
| 8 | `composer analyse` (first run) | 1 | 6 errors: resources' `getEloquentQuery()` Builder<Model> vs Builder<X> (fixed with `@extends FilamentResource<X>`), `grantingProfessional()` on Builder<Model> in the users plan filter (moved to a typed static method) |
| 9 | `composer analyse` (reruns) | 1 → 1 → 0 | final: [OK] No errors, **baseline unchanged (3 entries)** |
| 10 | `vendor/bin/pint --test` (whole project) | 1 | 5 issues: Pint's `phpdoc_types` rewrites `@extends Resource<X>` to `resource<X>` (fixed by importing `Filament\Resources\Resource as FilamentResource`); bootstrap/providers.php (written by filament:install in FEAT-002) formatted with Pint |
| 11 | `vendor/bin/pint --test` / `vendor/bin/rector --dry-run` / `composer analyse` (final code) | 0 / 0 / 0 | 382 files PASS / Rector is done / No errors |
| 12 | `php artisan test --filter="ActivityLog\|AccountDeletion"` | 0 | 36 passed (165 assertions) |
| 13 | `php artisan test --filter=Admin` | 0 | 106 passed (508 assertions) |
| 14 | `php artisan route:list --path=admin` | 0 | same 10 routes as #3; no wallet/transaction/goal/budget/category/receipt/media/AI route |
| 15 | `php artisan filament:optimize` | 0 | components + Blade icons cached |
| 16 | `php artisan filament:optimize-clear` | 0 | cleared |
| 17 | `composer analyse` | 0 | = #11 (same code, not repeated) |
| 18 | `php artisan test` | 0 | **797 passed, 15 skipped (3513 assertions)**, 75.6 s. The 15 skips are the intentional ApiEndpointMatrix dataset skips. FEAT-001's 16 date-dependent failures did not occur in this run (app date already past the month's last day) |

## FEAT-004 – Docs + full verification

Docs only; no code, `phpstan.neon` or baseline changes. `composer format` and a non-dry-run `vendor/bin/rector` were not needed because `composer quality` (which includes `pint --test` and `rector --dry-run`) was already clean on the first run. PHPStan baseline still 3 entries.

Run started 2026-10-31T22:27:38-03:00 (America/Sao_Paulo). Each command run alone, in this order, cwd = project root.

| # | Command | Exit | Result |
|---|---|---|---|
| 1 | `composer quality` (pint --test + phpstan level 6 + rector --dry-run + test) | 0 | lint/analyse/refactor clean; tests 797 passed, 15 skipped (3513 assertions), 73.7 s |
| 2 | `php artisan test` | 0 | **797 passed, 15 skipped (3513 assertions)**, 74.8 s |
| 3 | `php artisan test --order-by=random` | 0 | **797 passed, 15 skipped (3513 assertions)**, 74.2 s, seed 1793496623 |
| 4 | `php artisan route:list` | 0 | 175 routes |
| 5 | `php artisan l5-swagger:generate` | 0 | Regenerating docs default |
| 6 | `php artisan view:cache` | 0 | Blade templates cached successfully |
| 7 | `php artisan view:clear` | 0 | Compiled views cleared successfully |
| 8 | `php artisan filament:optimize` | 0 | components + Blade icons cached |
| 9 | `php artisan filament:optimize-clear` | 0 | cached components + Blade icons cleared |
| 10 | `npm run build` | 0 | built in 3.48 s (app css 143.03 kB, js 229.12 kB) |
| 11 | `php artisan migrate --pretend` | 0 | Nothing to migrate (the Phase 6 migration was already applied in FEAT-002) |

Baseline comparison: 672 passed / 15 skipped / 2734 assertions before Phase 6 → 797 passed / 15 skipped / 3513 assertions (+125 tests).

### The 15 skips

All 15 are intentional dataset skips in `tests/Feature/Api/ApiEndpointMatrixTest.php`. The matrix runs every API v1 endpoint through the same checks; 13 cases are skipped for endpoints that take no record id (the foreign-record 404 check does not apply), and 2 for endpoints that are not guarded by a Spatie permission (the missing-permission 403 check does not apply). The count is unchanged from the pre-Phase 6 baseline.

### Date-dependent failures

FEAT-001/FEAT-002 saw 16 failures from `Transaction::scopeInMonth()` on SQLite when run on the last day of the month (app time). They did not occur in this run, although it started on 2026-10-31 22:27 app time. Correction (integration step): the scope *was* changed during Phase 6. See the integration section below.

## Integration – cross-FEAT verification, iteration 1

No review.md existed, so this is iteration 1. Run 2026-10-31 22:33–22:45 America/Sao_Paulo (still the last day of the month in app time, i.e. the day that used to trigger the 16 month-filter failures). Each command run alone, in this order, cwd = project root.

| # | Command | Exit | Result |
|---|---|---|---|
| 1 | `composer quality` (pint --test + phpstan level 6 + rector --dry-run + test) | 0 | lint/analyse/refactor clean; tests 797 passed, 15 skipped (3513 assertions), 74.8 s |
| 2 | `php artisan test` | 0 | **797 passed, 15 skipped (3513 assertions)**, 74.0 s |
| 3 | `php artisan test --order-by=random` | 0 | **797 passed, 15 skipped (3513 assertions)**, 74.2 s, seed 1793496998 |
| 4 | `php artisan route:list` | 0 | 175 routes; `/admin` routes: dashboard, users (+view), subscriptions, payment-histories, activities (+view), login, logout, multi-factor-authentication/set-up. No wallet/transaction/goal/budget/category/receipt/media/AI admin route |
| 5 | `php artisan l5-swagger:generate` | 0 | Regenerating docs default |
| 6 | `php artisan view:cache` | 0 | Blade templates cached successfully |
| 7 | `php artisan view:clear` | 0 | Compiled views cleared successfully |
| 8 | `php artisan filament:optimize` | 0 | components + Blade icons cached |
| 9 | `php artisan filament:optimize-clear` | 0 | cached components + Blade icons cleared |
| 10 | `npm run build` | 0 | built in 3.76 s (app css 143.24 kB, js 229.12 kB) |
| 11 | `php artisan migrate --pretend` | 0 | Nothing to migrate. `migrate:status`: `2026_11_15_000100_add_suspension_and_mfa_to_users_table` Ran (batch 7). Its `up()` is additive only (3 nullable columns + 1 index; FEAT-002 #6 recorded the pretend SQL) |

Seam checks (read-only; no code change was needed):

- API v1 group order `[ThrottleApiByIp, auth:sanctum, active, verified, throttle:api, api.plan]` combines FEAT-001 (IP limiter + `prependToPriorityList`) and FEAT-002 (`active`); ApiIpRateLimitTest still proves the IP limiter sorts before Sanctum. The `api` limiter has no `?? $request->ip()` fallback. `.env.example` and `config/api.php` carry `API_IP_RATE_LIMIT_PER_MINUTE=120`.
- API tokens are only issued from the web settings page (`ApiTokenRepository::create`), which sits behind the web group and therefore `EnsureAccountIsActive`. A suspended user cannot mint a new token, and FEAT-003's `AdminUserService::suspend()` revokes existing ones.
- Filament panel routes do not use the `web` group. Suspension inside `/admin` is enforced by `User::canAccessPanel()` (403), covered by AdminAccessTest. The global `SecurityHeaders` CSP is present on `/admin/*`, and it already allows what Filament needs (`'unsafe-inline'`/`'unsafe-eval'` for Alpine/Livewire, `data:` images for avatars and the MFA QR code, Google Fonts). `SecurityHeaders.php` is unchanged.
- No `Gate::before` or new policies, so admins still do not bypass ownership in the user app.
- Writes in the panel go through `AdminUserServiceInterface` → `AccountDeletionServiceInterface::delete($target, $admin)` and `SecurityActivityLoggerInterface::log(..., $admin)` (FEAT-003 signatures used consistently by FEAT-002's console commands, which pass no causer).
- PHPStan baseline still 3 entries (no Filament/Livewire entries). Pint covers `app/` including `app/Filament`; Rector paths are app, config, database (migrations skipped), routes.
- Docs: `docs/admin/phase-6-filament.md` covers versions, first admin (`finestro:make-admin`), MFA + recovery codes + `finestro:admin-mfa-reset`, access rules, guest redirect to `/admin/login`, data minimisation, every resource/action, CSP decision, suspension. `docs/api/phase-5-api.md` covers the `api-ip` limiter, the Scout subquery engine and the wallet update contract.

Month-filter note (correction to FEAT-001/FEAT-004): `app/Models/Transaction.php` was rewritten on 2026-10-31 22:12 (during FEAT-003, by another FEAT step or an IDE hook; not by this step and not reverted). `scopeInMonth()` now filters with a half-open range, `date >= Y-m-01 AND date < first day of next month`, instead of `whereBetween('date', ['Y-m-01', 'Y-m-t'])`. That removes the SQLite cause of the 16 last-day-of-month failures. Runs 1-3 above executed on 2026-10-31 (app time) with 0 failures, which confirms the fix on the day that used to fail.

The 15 skips are the intentional ApiEndpointMatrixTest dataset skips explained in the FEAT-004 section (13 endpoints without a record id, 2 without a Spatie permission), unchanged from the baseline. Totals vs. the pre-Phase 6 baseline: 672 → 797 passed (+125), 15 → 15 skipped, 2734 → 3513 assertions.

## Integration – review fixes, iteration 2

Input: review.md / verdict.json (CHANGES_REQUESTED, 7 findings). Findings 2 (wallet clear) and 5 (promote rules) change behaviour, so they were raised with send_message (warning); the answer was "A1, B1", with the condition that a field may be cleared only where the store rules allow null for that wallet type.

Fixes:

1. **CSV formula injection (blocking)**: `AdminPaymentsExport::text()` adds a leading `'` to name, e-mail, currency and description values that start with `=`, `+`, `-`, `@`, tab or CR (both writers). New test in `BillingResourcesTest` (HYPERLINK name, `+` e-mail, `@` description, tab/CR, leading `-`, normal text untouched). Docs §8 corrected.
2. **Wallet clear (A1)**: `WalletDTO::forUpdate()` marks `company_name`, `company_document`, `credit_limit`, `closing_day`, `due_day` as cleared when present and null (blank web input or JSON null); `toArray()` keeps those nulls. Omitted fields and other null fields still keep their value. Used by the web and API update controllers. Condition check: `StoreWalletRequest` declares all five fields `nullable` for every wallet type, the forms do not mark them required, and a card without both days is already a supported state (`Wallet::hasInvoiceCycle()` false; `CreditCardInvoiceService::getInvoice()` returns null; the invoice page redirects to the edit form with "Informe o dia de fechamento..."). So no wallet type requires them and there is no rejected-clear case; invalid values still fail with 422 / pt-BR session errors (existing datasets). Tests: web clear on a non-card wallet, web clear on a card wallet (+ invoice redirect), web omitted fields kept; API PATCH/PUT null clears business fields on a non-card wallet (description null kept), API PATCH/PUT null clears card fields and keeps omitted `due_day`; DTO unit check. OpenAPI (`WalletUpdateInput`, PUT/PATCH descriptions) and docs/api/phase-5-api.md updated; Swagger regenerated.
3. **Month-end regression test**: new `tests/Feature/TransactionMonthFilterTest.php` pinned with `travelTo` to 2026-10-31, 2026-02-28, 2028-02-29 and 2026-12-31: the last-day row appears in `inMonth()`, the default and explicit month list and the summary; neighbouring days do not.
4. **Direct-URL matrix**: `AdminAccessTest` datasets over `/admin/users`, `/admin/users/{id}`, `/admin/subscriptions`, `/admin/payment-histories`, `/admin/activities`, `/admin/activities/{id}`: guest → `/admin/login`; plain user, admin without `admin.access`, unverified admin, suspended admin → 403; MFA admin → 200.
5. **Promote rules (B1)**: `AdminUserService::promote()` refuses unverified and suspended targets with the same pt-BR messages as `finestro:make-admin` (constants `AdminUserServiceInterface::PROMOTE_UNVERIFIED_MESSAGE` / `PROMOTE_SUSPENDED_MESSAGE`, used by both). The panel shows a danger notification and logs nothing. Tests in `AdminUserServiceTest` and `UserActionsTest`; docs §8 table updated.
6. **MFA first-enrolment window**: docs §5 step 4 and §6 explain the window and the operating rule (set up personally right after promotion/reset; reset password first if it may be known; re-reset and suspend if an enrolment looks wrong).
7. **Stray files**: `b.txt`, `loop1.txt`–`loop6.txt` (Pest console output from another process's test loop, finished at 22:50:59) deleted from the project root; no `*.txt` left there.

Run 2026-10-31 ~22:55–23:10 America/Sao_Paulo. Each command run alone, cwd = project root.

| # | Command | Exit | Result |
|---|---|---|---|
| 1 | `php artisan test --filter="WalletApi\|WalletUpdate\|WalletAccess\|OpenApiSpec\|DtoDataConversion\|TransactionMonthFilter\|TransactionSearch\|AdminAccess\|BillingResources\|AdminUserService\|UserActions\|AdminCommands"` | 0 | 165 passed (904 assertions) |
| 2 | `php artisan l5-swagger:generate` | 0 | Regenerating docs default |
| 3 | `composer quality` (pint --test + phpstan level 6 + rector --dry-run + test) | 0 | lint/analyse/refactor clean; tests 852 passed, 15 skipped (3688 assertions), 78.5 s |
| 4 | `php artisan test` | 0 | **852 passed, 15 skipped (3688 assertions)**, 74.6 s |
| 5 | `php artisan test --order-by=random` | 0 | **852 passed, 15 skipped (3688 assertions)**, 74.5 s, seed 1793498445 |
| 6 | `php artisan route:list` | 0 | 175 routes (unchanged) |
| 7 | `php artisan view:cache` / `php artisan view:clear` | 0 / 0 | cached / cleared |
| 8 | `php artisan filament:optimize` / `php artisan filament:optimize-clear` | 0 / 0 | components + Blade icons cached / cleared |
| 9 | `npm run build` | 0 | built in 3.25 s (css 142.96 kB, js 229.12 kB) |
| 10 | `php artisan migrate --pretend` | 0 | Nothing to migrate (no new migration in this iteration) |

PHPStan baseline still 3 entries. The 15 skips are the intentional ApiEndpointMatrixTest dataset skips (13 endpoints without a record id, 2 without a Spatie permission). Totals vs. the pre-Phase 6 baseline: 672 → 852 passed (+180), 15 → 15 skipped, 2734 → 3688 assertions. Iteration 1 had 797 / 15 / 3513; this iteration adds 55 tests.

## Finalisation

No code changed in this step. Run 2026-10-31 ~23:08-23:12 America/Sao_Paulo, each command alone, cwd = project root.

| # | Command | Exit | Result |
|---|---|---|---|
| 1 | `php artisan test` | 0 | **852 passed, 15 skipped (3688 assertions)**, 76.5 s |
| 2 | `composer quality` (pint --test + phpstan level 6 + rector --dry-run + test) | 0 | lint/analyse/refactor clean; tests 852 passed, 15 skipped (3688 assertions), 76.9 s |

Same totals as review iteration 2. The 15 skips are the intentional ApiEndpointMatrixTest dataset skips. task.json status set to `completed`.
