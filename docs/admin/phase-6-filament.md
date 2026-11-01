# Phase 6 — Filament admin panel

> Laravel 13.9.0 · PHP 8.5.6 · Filament v5.11.0 · Livewire v4.4.8

Finestro has an internal admin panel at `/admin`, built with FilamentPHP. It manages accounts, subscriptions and payments and shows the activity log. It never shows the financial content of a user (wallets, transactions, goals, budgets, categories, receipts, AI data).

## 1. Installed versions

| Package | Version | Why |
|---|---|---|
| `filament/filament` (all `filament/*`) | v5.11.0 | Panel, tables, infolists, actions, widgets |
| `livewire/livewire` | v4.4.8 | Required by Filament 5 |
| `pragmarx/google2fa` | v9.1.0 | TOTP codes for the panel MFA |
| `chillerlan/php-qrcode` | 5.0.5 | QR code shown during MFA set-up |

`laravel/framework` stayed at v13.9.0; the install added packages without updating or removing any. Versions were read with `composer show <package>`.

The panel uses Filament's prebuilt CSS/JS/fonts published to `public/css/filament`, `public/js/filament` and `public/fonts/filament`. There is no custom Filament theme, and `package.json`, `vite.config.js`, `tailwind.config.js` and `resources/css/app.css` were not changed.

## 2. Panel, guard and session

- URL: `/admin` (provider `app/Providers/Filament/AdminPanelProvider.php`, panel id `admin`).
- Guard: the panel authenticates with its own session guard `admin` (`config/auth.php`, driver `session`, provider `users`; `web` stays the default guard).
  - Reason: Filament only asks for the MFA code on its own login page. With the shared `web` guard, an admin logged in to the user app (no MFA) would enter `/admin` without a second factor. The separate guard forces every admin through `/admin/login` (e-mail + password + TOTP).
- Guests who open any `/admin` URL are redirected to `/admin/login`, not to the user-app login.
- Both guards share the same Laravel session. Logging out of either app invalidates the session, so it ends both logins.
- `App\Models\User` pins `protected string $guard_name = 'web'`. Filament switches the default guard to `admin` inside the panel; without the pin Spatie Permission would look for `admin`-guard roles and throw `RoleDoesNotExist`/`PermissionDoesNotExist`.
- Branding: name "Finestro", logo `resources/views/filament/admin/brand.blade.php`, `favicon.svg`, primary colour `#039855`, dark mode, footer "Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL" (`resources/views/filament/admin/footer.blade.php`). UI labels are pt-BR (Filament ships pt_BR translations; `APP_LOCALE=pt_BR`).
- Not enabled on purpose: `->profile()` (admins cannot change e-mail/password in the panel), `->spa()`, global search.

## 3. Access rule and authorization

`User::canAccessPanel(Panel $panel)` returns true only when all of these hold:

1. the panel id is `admin`;
2. the user is not suspended (`suspended_at` is null);
3. the e-mail is verified;
4. the user has the Spatie role `admin`;
5. the user has the permission `admin.access`.

When the rule fails, Filament's `Authenticate` middleware returns 403 and the login page rejects the user.

Inside the panel, authorization uses Filament's `can*()` methods, not model policies. `App\Filament\Concerns\RequiresAdminAccess` overrides the single static `getAuthorizationResponse()` used by every `can*()` method: only `viewAny`/`view` are allowed, and only with `admin.access`. Create, edit, delete, restore and force-delete are always denied. No `UserPolicy`/`SubscriptionPolicy` was registered, so Gate behaviour in the user app (audited in Phase 1) is unchanged. Every custom action also calls `->authorize(...)`, so a forged Livewire request from a non-admin is refused on the server (`tests/Feature/Admin/LivewireAuthorizationTest.php`).

## 4. Login throttling

- Filament's login page allows 5 attempts per minute per IP (`danharrin/livewire-rate-limiting`).
- The MFA challenge has its own rate limiter.
- Both are Filament defaults, kept and covered by `tests/Feature/Admin/AdminLoginTest.php`.

## 5. First admin

There is no default admin and no password in code, seeders or `.env`.

1. Register through the normal app (`/register`) with username, e-mail and password.
2. Confirm the e-mail through the verification link.
3. Run `php artisan finestro:make-admin email@example.com`.
   - Fails (exit 1) for unknown, soft-deleted, unverified or suspended users.
   - Idempotent when the user is already an admin.
   - Logs the security event `admin_role_assigned` with `{role: admin, via: console}`.
4. Straight away, the new admin opens `/admin/login` personally and signs in. The MFA set-up page opens on the first login (see the first-enrolment window in section 6).

Do not use `php artisan make:filament-user`: it creates a user without `username`, roles or e-mail verification.

## 6. MFA (required)

- Configuration: `->multiFactorAuthentication([AppAuthentication::make()->recoverable()->brandName('Finestro')], isRequired: true)`.
- First login: scan the QR code (rendered as a `data:` SVG) with an authenticator app (Google Authenticator, Authy, 1Password ...) and confirm a code.
- Recovery codes: Filament shows 8 recovery codes at set-up. Store them offline (password manager or printed copy). Each code works once, in place of a TOTP code.
- Secret and recovery codes are stored encrypted in `users.app_authentication_secret` and `users.app_authentication_recovery_codes` (Filament trait casts) and are in `$hidden`.
- Lost device and lost recovery codes: a server operator runs `php artisan finestro:admin-mfa-reset email@example.com` (asks for confirmation; `--force` skips it). It clears both columns and logs `admin_mfa_reset` with `{via: console}`. The admin must set up MFA again on the next login.
- First-enrolment window: while an admin has no TOTP secret (right after `finestro:make-admin`, "Tornar administrador" in the panel, or `finestro:admin-mfa-reset`), Filament sends them to the set-up page after the password alone. Whoever knows the password at that moment enrols the authenticator. This is how Filament's required MFA works. Operating rule: the admin completes the set-up personally, straight after the promotion or reset, on their own device. Do not promote or reset an account whose password may be known to someone else; ask the user to reset the password first (`/forgot-password`). If an enrolment looks wrong (unexpected `admin_role_assigned` or `admin_mfa_reset` entry, or the admin did not do the set-up), run `finestro:admin-mfa-reset` again and suspend the account until the owner confirms.

## 7. Data minimisation

- Resources exist only for `User`, `Subscription`, `PaymentHistory` and `Spatie\Activitylog\Models\Activity`. `tests/Feature/Admin/DataMinimisationTest.php` asserts this and that no `/admin` route path contains wallet, transaction, goal, budget, category, receipt, media or AI.
- The user view shows account, access and billing fields only.
- Activity entries whose subject is a financial model (`App\Support\Admin\FinancialSubjects::isFinancial()`: Wallet, Transaction, Goal, Budget, Category, AiCredential, TransactionHistory, WalletMember, Media) show only event, subject type and date, with the note "Detalhes ocultos (dados financeiros do usuário)". Their `attribute_changes` and `properties` are never rendered.
- Global search is disabled (`->globalSearch(false)`).
- Avatars come from `App\Filament\AvatarProviders\InitialsAvatarProvider`, a local `data:image/svg+xml` with the initials. Admin names are not sent to ui-avatars.com.
- Stripe ids are shortened in the UI (`App\Support\StripeId::short()`, e.g. `sub_…1a2b`) and are never exported.

## 8. Resources and actions

All reads go through repositories (`AdminUserRepositoryInterface`, `AdminBillingRepositoryInterface`, `ActivityRepositoryInterface::adminQuery()`); all writes go through `AdminUserServiceInterface`. There are no create or edit pages, no password fields and no impersonation.

### Usuários (`/admin/users`, `/admin/users/{id}`)

Table: name, username, e-mail, verified, plan (Free/Profissional), roles, suspended, created at. Search on name, username and e-mail. Filters: plan (via `Subscription::scopeGrantingProfessional()`), role (admin/user), verified.

Actions (table row group and view page header). Each one asks for confirmation, is visible only in the matching state, and authorizes with `admin.access`. Guard failures raise `AdminActionException` (or `AccountDeletionException`) and show a danger notification; nothing changes.

| Action (label) | Service method | Guards | Activity event (log `security`, causer = admin) |
|---|---|---|---|
| Tornar administrador | `promote` | target not already admin; e-mail verified; not suspended (same rules and pt-BR messages as `finestro:make-admin`, constants on `AdminUserServiceInterface`) | `admin_role_assigned` `{role: admin, via: admin}` |
| Remover administrador | `demote` | not on yourself; target is admin; at least one other active admin remains | `admin_role_removed` `{role: admin, via: admin}` |
| Suspender | `suspend` | not on yourself; not already suspended; if the target is an admin, another active admin remains | `admin_user_suspended` `{via: admin}` |
| Reativar | `unsuspend` | target is suspended | `admin_user_unsuspended` `{via: admin}` |
| Reenviar verificação | `resendVerification` | e-mail not verified yet; action rate limit 5 | `admin_verification_resent` `{via: admin}` |
| Excluir conta | `delete` | not on yourself; last-admin guard; Stripe cancellation rules of `AccountDeletionService` | `admin_user_deleted` `{via: admin}` and `account_deleted` (causer = admin) |

"Active admin" means role `admin`, not suspended, not soft-deleted. Delete calls `AccountDeletionServiceInterface::delete($target, $admin)`: the open Stripe subscription is cancelled first. If Stripe reports the resource as missing, the subscription is marked cancelled locally and the deletion continues. Any other Stripe error keeps the account and shows the pt-BR message from `AccountDeletionException`.

### Assinaturas (`/admin/subscriptions`, read-only)

Columns: user name/e-mail, plan, status label, current period start–end, "cancela no fim do período" (Sim/Não), shortened Stripe subscription id. Filter: status (`SubscriptionStatus` labels). No actions.

### Pagamentos (`/admin/payment-histories`, read-only)

Columns: user, amount in BRL (`PlanPricingInterface::format()`, e.g. `R$ 1.234,56`), status label, date (`COALESCE(paid_at, created_at)`), invoice link (opens in a new tab) when the payment has one. Filters: status and month (last 12 `Y-m` values).

Header actions "Exportar CSV" and "Exportar XLSX" (admin only, `admin.access`) download `pagamentos-YYYYmmdd-His.csv|xlsx` built by `App\Exports\AdminPaymentsExport` from the currently filtered table query, newest first:

- Columns: Data, Usuário, E-mail, Valor, Moeda, Situação, Descrição.
- CSV uses `;` as separator with a UTF-8 BOM (same pattern as `TransactionsExport`).
- Formula injection: user name, e-mail, currency and description are text supplied by users or Stripe. `StringValueBinder` only types XLSX cells as strings; CSV has no cell types, so a spreadsheet would evaluate a value that starts with `=`, `+`, `-`, `@`, tab or carriage return. In the CSV writer `AdminPaymentsExport::text()` prefixes such values with an apostrophe (`'`), so they open as plain text (e.g. `'=HYPERLINK(...)`). In XLSX the export keeps the original text: `StringValueBinder` writes it with `setValueExplicit(TYPE_STRING)`, so no apostrophe is visible and no formula is evaluated (Phase 6.5, see `docs/security/phase-6-5-dependency-update.md`). The header action passes its writer type to the export. Covered by `BillingResourcesTest`.
- No Stripe ids, invoice URLs or secrets.

### Atividade (`/admin/activities`, `/admin/activities/{id}`, read-only)

Columns: date, log (`security` = Segurança, `model` = Dados), event (pt-BR label), causer name/e-mail, subject type (short class name). Filters: causer (searchable, repository-backed), event, period (from/until). The view page applies the financial-subject rule from section 7; other entries show the stored `attribute_changes` and `properties`.

Admin event names: `admin_role_assigned`, `admin_role_removed`, `admin_user_suspended`, `admin_user_unsuspended`, `admin_verification_resent`, `admin_user_deleted`, `admin_mfa_reset`. `SecurityActivityLoggerInterface::log()` now takes an optional `?User $causer`; precedence is `$causer ?? Auth::user() ?? $subject` (`login_failed` stays anonymous).

## 9. Dashboard metrics

Widget `AdminStatsOverview` reads `AdminMetricsServiceInterface::metrics()` (`App\DTOs\AdminMetricsData`):

| Stat | DTO fields | Definition |
|---|---|---|
| Usuários | `totalUsers`, `newUsersLast30Days` | users not soft-deleted; users created in the last 30 days |
| Free × Profissional | `freeUsers`, `professionalUsers` | Profissional = users whose current (latest) subscription matches `Subscription::scopeGrantingProfessional()`: plan `professional`, period end null or in the future, and status active/trialing or (canceled with a period end). The scope mirrors `Subscription::isProfessional()`; a test compares both over every factory state. Free = total − Profissional |
| Assinaturas ativas | `activeSubscriptions`, `pastDueSubscriptions`, `canceledSubscriptions` | subscriptions counted by status `active`, `past_due`, `canceled` |
| MRR estimado | `mrrEstimate`, `mrrFormatted` | number of subscriptions with plan `professional`, status `active` and period end null or future × `PlanPricingInterface::regularPrice()`. Promotional prices and trials are ignored, so it is an estimate; the price is never hard-coded |
| Pagamentos no mês | `paymentsThisMonthCount`, `paymentsThisMonthTotal` | count and sum of payments with status `paid` and `paid_at` in the current month |

Widget `StripeConfigWarning` appears only when `StripeConfigCheckInterface::isTestKeyInProduction()` is true (test Stripe key with `APP_ENV=production`). It never shows the key. The default `AccountWidget` is kept; `FilamentInfoWidget` was removed.

## 10. Suspension

Column `users.suspended_at` (nullable, indexed; not mass-assignable, set with `forceFill`). Message: "Sua conta está suspensa. Entre em contato com o suporte para mais informações." (`User::SUSPENDED_MESSAGE`).

| Where | Behaviour |
|---|---|
| User-app login | `LoginRequest` uses `attemptWhen`. With the right password the user sees the suspension message; with a wrong password the generic `auth.failed` message (no account enumeration) |
| Active user-app sessions | `EnsureAccountIsActive` (appended to the `web` group) logs the user out on the next request, invalidates the session and redirects to login with the message |
| API v1 | `active` middleware right after `auth:sanctum` returns 403 `forbidden` with the message. `suspend` also deletes all Sanctum tokens |
| Admin panel | `canAccessPanel()` returns false → 403 / login refused. The panel routes do not use the `web` group, so this check is the enforcement there |
| Reativar | clears `suspended_at`. Revoked API tokens are **not** restored; the user creates new ones |

## 11. CSP decision

`app/Http/Middleware/SecurityHeaders.php` was **not** changed. `tests/Feature/Admin/AdminCspTest.php` checks every external origin in the `/admin/login` HTML against the CSP header and found no violation:

- Filament CSS/JS/fonts are self-hosted (`'self'`).
- The Inter font uses `GoogleFontProvider` (`fonts.googleapis.com` / `fonts.gstatic.com`, already allowed) instead of Filament's default Bunny Fonts.
- Avatars and the MFA QR code are `data:` URIs (`img-src` already allows `data:`).
- Livewire update responses are JSON, so CSP does not apply to them; Alpine's need for `'unsafe-eval'` is already covered by the existing `script-src`.

If a future Filament plugin needs another origin, add it only for `admin/*` requests and document it here.

## 12. Deploy notes

The `public/css/filament`, `public/js/filament` and `public/fonts/filament` folders are git-ignored, so they must be rebuilt on each deploy:

```bash
composer install --no-dev --optimize-autoloader   # post-autoload-dump runs `php artisan filament:upgrade`
php artisan filament:assets                       # only if the hook did not run (e.g. --no-scripts)
php artisan migrate --force                       # adds users.suspended_at + MFA columns (additive)
php artisan optimize
php artisan filament:optimize                     # caches Filament components and Blade icons
```

`php artisan filament:optimize-clear` removes the Filament cache (run it before debugging panel discovery issues).

## 13. Follow-up: composer audit

`composer require` and `composer audit` report 41 security advisories in 11 packages that were installed before Phase 6 (guzzlehttp/*, laravel/framework, symfony/*, league/commonmark, league/flysystem). Filament did not introduce any of them. Upgrading those packages, including Laravel, was out of scope for this phase and is a manual follow-up: run `composer audit`, update the affected packages, then run the full test suite.

## 14. Tests

New Pest tests live in `tests/Feature/Admin/`: AdminAccess, AdminLogin, AdminCsp, Suspension, AdminCommands, AdminUserService, AdminMetricsService, UserResource, UserActions, BillingResources, ActivityResource, DashboardWidgets, DataMinimisation, LivewireAuthorization. Filament tests set the panel with `Filament::setCurrentPanel(Filament::getPanel('admin'))`, log in with `actingAs($user, 'admin')` and use the `mfaAdmin()` helper from `tests/Pest.php`.
