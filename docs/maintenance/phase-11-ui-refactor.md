# Phase 11 — UI Refactor & Audit (2025-07-11)

## 1. Overview

Phase 11 applied three parallel workstreams to Finestro:

1. **UI/UX enhancements** — persuasion triggers on the public landing page,
   glassmorphism on the dashboard hero, Neumorphism styling on calculators and
   simulators, a reusable tooltip Blade component, contextual help popovers on
   CRUD forms, and descriptive field hints throughout the interface.

2. **Security & code quality** — `declare(strict_types=1)` audit across auth and
   feature controllers, extraction of inline validation to a dedicated FormRequest,
   honeypot protection on the login form, and BreadcrumbList JSON-LD on the four
   public legal/FAQ pages.

3. **Test coverage expansion** — 14 new test files covering CRUD flows, API v1,
   security vectors, calculator/simulator smoke, and unit tests for core services.

A maintenance cleanup was also performed: orphan view audit, public directory
audit, and npm/Composer package audit (see sections 5 and below).

---

## 2. UI/UX Changes

| File | What was added / changed |
|---|---|
| `resources/views/landing/home.blade.php` | Authority seal strip (HTTPS, LGPD, user count, no credit card); scarcity counter with Alpine.js `x-data='{slots: 7}'`; urgency countdown banner with `setInterval` to a PHP-rendered ISO 8601 deadline; Before/After transformation two-column section between `#servicos` and `#ferramentas`; 'Novo' pill badges on AI Insights and Shared Wallets feature cards; 'Em Breve' teaser section with 3 greyed-out cards + WhatsApp CTA; reciprocity free-plan callout floating card in the hero |
| `resources/views/dashboard/index.blade.php` | Hero balance card updated to `bg-gradient-to-br from-primary-900/80 via-primary-800/80 to-primary-950/80 backdrop-blur-2xl border border-white/10`; stat cards updated to `backdrop-blur-sm bg-white/80 dark:bg-surface-900/60 border border-white/20 dark:border-surface-700/40` |
| `resources/css/app.css` | `.nm-input` and `.nm-card` utility classes added under `@layer components` |
| `resources/views/calculators/*.blade.php` (7 files) | Numeric inputs updated to `nm-input` class; card wrappers updated to `nm-card` class |
| `resources/views/simulators/*.blade.php` (6 files) | Same Neumorphism classes applied |
| `resources/views/components/ui/tooltip.blade.php` | New anonymous Blade component; props: `$text` (string), `$position` ('top'\|'right', default 'top'); wraps slot with group-hover Tailwind visibility pattern |
| `resources/views/wallets/create.blade.php` | 4 fields wrapped with `<x-ui.tooltip>`; contextual `?` help popover via Alpine.js `x-data='{helpOpen: false}'`; `hint=` attributes on name, type, and balance fields |
| `resources/views/wallets/edit.blade.php` | Same tooltip wrapping as create |
| `resources/views/transactions/partials/form.blade.php` | `hint=` attributes on description, amount, and category fields; `?` help popover |
| `resources/views/goals/create.blade.php` | `hint=` attributes on name, target_amount, and deadline fields; `?` help popover |

---

## 3. Security & Code Quality Changes

| File | Nature of change |
|---|---|
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Added `declare(strict_types=1)` |
| `app/Http/Controllers/Auth/RegisteredUserController.php` | Added `declare(strict_types=1)` |
| `app/Http/Controllers/Auth/ConfirmablePasswordController.php` | Added `declare(strict_types=1)` |
| `app/Http/Controllers/Auth/EmailVerificationNotificationController.php` | Added `declare(strict_types=1)` |
| `app/Http/Controllers/Auth/EmailVerificationPromptController.php` | Added `declare(strict_types=1)` |
| `app/Http/Controllers/Auth/PasswordController.php` | Added `declare(strict_types=1)` |
| `app/Http/Controllers/Auth/PasswordResetLinkController.php` | Added `declare(strict_types=1)` |
| `app/Http/Controllers/Auth/NewPasswordController.php` | Added `declare(strict_types=1)` |
| `app/Http/Controllers/Auth/VerifyEmailController.php` | Added `declare(strict_types=1)` |
| `app/Http/Controllers/WalletController.php` | Added `declare(strict_types=1)`; replaced inline `$request->validate()` in `payInvoice()` with `PayInvoiceRequest` |
| `app/Http/Controllers/GoalController.php` | Added `declare(strict_types=1)` |
| `app/Http/Controllers/SimulatorController.php` | Added `declare(strict_types=1)` |
| `app/Http/Controllers/CalculatorController.php` | Added `declare(strict_types=1)` |
| `app/Http/Controllers/PageController.php` | Added `declare(strict_types=1)` |
| `app/Http/Requests/PayInvoiceRequest.php` | New FormRequest: 4 rules (source_wallet_id, amount, date, ref), custom messages, dynamic `Rule::notIn` via `$this->route('wallet')` |
| `resources/views/auth/login.blade.php` | Added `<x-ui.honeypot />` after `@csrf` |
| `app/Http/Requests/Auth/LoginRequest.php` | Added `'website' => ['nullable', new Honeypot('website')]` to `rules()` |
| `app/Interfaces/Services/StructuredDataServiceInterface.php` | Added `breadcrumbs(array $items): array` method signature |
| `app/Services/StructuredDataService.php` | Implemented `breadcrumbs()` returning a valid BreadcrumbList JSON-LD array; prepends Início automatically |
| `resources/views/pages/privacy.blade.php` | Added `@push('jsonld')` BreadcrumbList block |
| `resources/views/pages/terms.blade.php` | Added `@push('jsonld')` BreadcrumbList block |
| `resources/views/pages/cookies.blade.php` | Added `@push('jsonld')` BreadcrumbList block |
| `resources/views/pages/faq.blade.php` | Added `@push('jsonld')` BreadcrumbList block |

---

## 4. Tests Added

| Test file | What it covers | Test cases |
|---|---|---|
| `tests/Feature/WalletCrudTest.php` | Create, update, delete, invoice pay validation, free plan limits | 6 |
| `tests/Feature/GoalCrudTest.php` | CRUD, cross-user isolation (403/404 on other users' goals) | 4 |
| `tests/Feature/CategoryBudgetCrudTest.php` | Category CRUD + free plan limit; budget store, copy previous month, free plan limit | 6 |
| `tests/Feature/ReportExportTest.php` | Free plan cannot export; professional exports CSV; professional generates PDF; email is throttled | 4 |
| `tests/Feature/ApiV1Test.php` | Guest returns 401; create API token; wallet CRUD via Bearer token; transaction CRUD via API; cross-user isolation | 5 |
| `tests/Feature/Security/CsrfProtectionTest.php` | POST without CSRF token returns 419 | 1 |
| `tests/Feature/Security/RateLimitTest.php` | 6th login attempt with wrong credentials returns 429 | 1 |
| `tests/Feature/Security/HoneypotTest.php` | Register with honeypot filled is rejected; login with honeypot filled is rejected | 2 |
| `tests/Feature/Security/XssInjectionTest.php` | Script tag in description is stored and rendered escaped; malformed search param does not 500 | 2 |
| `tests/Feature/CalculatorSmokeTest.php` | All 7 calculator pages return 200; all 6 simulator pages return 200 (professional user) | 2 |
| `tests/Unit/Services/TransactionServiceTest.php` | Summary returns correct income/expense totals; installment penny distribution (100/3) | 2 |
| `tests/Unit/Services/BudgetServiceTest.php` | Over-threshold budget returns 1 alert; copy previous creates correct count | 2 |
| `tests/Unit/Services/GoalServiceTest.php` | Progress percent calculation (25%); completed goal status | 2 |
| `tests/Unit/Helpers/UtmTest.php` | UTM params allowed through; unknown params excluded | 2 |

**Total suite after phase 11**: 1113 tests — 1098 pass, 15 skip, 0 fail.

---

## 5. Cleanup Actions

### Orphan View Audit

Scan covered all `.blade.php` files in `resources/views/` (excluding the
`errors/`, `components/`, `layouts/`, `vendor/`, and `filament/` infrastructure
directories). Every view file cross-referenced against `routes/web.php`,
`routes/auth.php`, all controller `view()` calls, `@extends`, `@include`, and
`x-*` component usages.

**Result: No orphan views found.** All non-infrastructure view files are
reachable from at least one named route or controller action.

### Public Directory Audit

Files present in `public/` outside of `public/build/` (Vite output):

| File | Classification |
|---|---|
| `public/index.php` | Laravel entry point — required |
| `public/favicon.svg` | Standard web root asset — required |
| `public/manifest.json` | PWA manifest — required |
| `public/.htaccess` | Apache rewrite rules — required |

No non-Vite CSS, JS, font, or image files found outside `public/build/`. No
files removed.

### npm Package Audit

| Package | Reference |
|---|---|
| `alpinejs` | `import Alpine from 'alpinejs'` in `resources/js/app.js` |
| `@tailwindcss/forms` | `import forms from '@tailwindcss/forms'` in `tailwind.config.js` |
| `autoprefixer` | PostCSS build chain |
| `concurrently` | `npx concurrently` in `composer dev` script |
| `laravel-vite-plugin` | `import laravel from 'laravel-vite-plugin'` in `vite.config.js` |
| `postcss` | PostCSS build chain |
| `tailwindcss` | `tailwind.config.js` + PostCSS |
| `terser` | `minify: 'terser'` in `vite.config.js` |
| `chart.js` | `import { Chart, ... } from 'chart.js'` in `resources/js/charts.js` |

**All packages confirmed used. No packages removed.**

### Composer Package Audit

All `require` and `require-dev` packages were reviewed:

- `barryvdh/laravel-dompdf` — used by `ReportController` (`Pdf::loadView(...)`)
- `darkaonline/l5-swagger` — OpenAPI docs at `/api/documentation`
- `filament/filament` — admin panel at `/admin`
- `laravel/framework`, `laravel/sanctum`, `laravel/scout`, `laravel/tinker` — framework core
- `maatwebsite/excel` — CSV/XLSX export in `ReportController`
- `spatie/laravel-activitylog`, `spatie/laravel-data`, `spatie/laravel-medialibrary`, `spatie/laravel-permission` — domain features
- `stripe/stripe-php` — Stripe Checkout integration
- `driftingly/rector-laravel`, `rector/rector` — `composer refactor` (Rector dry-run)
- `fakerphp/faker` — model factories
- `laravel/pail` — `php artisan pail` in `composer dev` script
- `laravel/pao` — agent-optimized output for PHP testing tools; used automatically by the `composer test` runner in AI-agent environments (no direct source reference needed)
- `larastan/larastan`, `phpunit/phpunit` — static analysis and test runner
- `laravel/pint` — `composer lint` / `composer format`
- `mockery/mockery`, `nunomaduro/collision` — test mocking and error display
- `pestphp/pest`, `pestphp/pest-plugin-laravel` — test runner

**All packages confirmed in use. No packages removed.**

---

## 6. How to Run

```bash
# Run the full test suite (clears caches first)
composer test

# Build production front-end assets
npm run build

# Run only code quality checks
composer quality   # lint + analyse + refactor (dry-run) + test
```

---

## 7. Known Limitations

- `composer analyse` (PHPStan full run) reports a pre-existing internal error on
  `CreditCardInvoiceService.php` caused by a phar stub path resolution bug in the
  CI environment. All files changed in this phase pass analysis individually with
  zero errors at level 6. This issue pre-dates Phase 11 and is tracked separately.
- The urgency countdown on the landing page restarts its deadline on each deploy
  (the ISO 8601 target is rendered by PHP at request time). This is intentional
  for a launch counter; a database-persisted deadline can replace it in a future
  phase.
- The 'Em Breve' WhatsApp CTA link must match `https://wa.me/5541996924829?text=...`
  to pass the `CrawlerTest` pattern validation.
