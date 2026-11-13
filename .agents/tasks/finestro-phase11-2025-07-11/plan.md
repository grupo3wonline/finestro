# Implementation Plan — Phase 11: Finestro Comprehensive Audit & Refactor

> **Branch:** `feature/phase11-ui-refactor`
> **Commands:** `composer test` (Pest 4 via `php artisan test`) · `npm run build` (Vite 5)
> **Constraint:** Do NOT touch `routes/api.php`, `.env`, or any Filament admin view.

---

## Exploration Summary

The codebase is already mature. The following are **already in place** and must not be re-introduced:

- Service / Interface / Repository architecture with AppServiceProvider bindings
- `declare(strict_types=1)` in almost all PHP files (auth controllers are the gap)
- SEO/meta/OG/Twitter Card/canonical/hreflang/GEO via `components/layout/seo-head.blade.php`
- JSON-LD WebSite + Organization + FAQPage + HowTo on `home` route via `StructuredDataService`
- Dynamic `robots.txt`, `sitemap.xml`, `llms.txt` routes (PageController)
- Dark/light toggle — Alpine.js `darkMode` store + localStorage + `dark` class on `<html>`
- Font Awesome 6 (CDN, non-blocking), SweetAlert2 (CDN, deferred), Inter + Fraunces (Google Fonts, preconnect)
- Cookie consent (LGPD/GDPR/CCPA/POPIA) in `components/layout/cookie-consent`
- Universal header (`components/layout/header.blade.php`) + footer (`components/layout/footer.blade.php`) in all 3 layouts
- Breadcrumb component (`x-ui.breadcrumb`) used in every app/auth/pages view
- Loading overlay (`components/ui/loading-overlay`) + `showLoading()`/`hideLoading()` in `app.js`
- Honeypot (`x-ui.honeypot`) on register; `HoneypotRule` class and time-trap in `RegisteredUserController`
- Double-submit protection (`forms.js` → `bootForms()`), `data-confirm` SweetAlert2 confirmations
- `autocomplete="off"` on all forms, `@csrf` on all POST forms
- PRG pattern throughout
- No unsafe `{!! !!}` raw output
- Glassmorphism utilities (`.glass`, `.glass-border`) and Aurora gradient in hero
- Error pages: 401, 403, 404, 419, 429, 500, 503 all exist in `resources/views/errors/`
- Pages: privacy, terms, cookies, contact, faq — all complete
- Tests: auth (5), smoke (25+), billing (6), transaction lifecycle (10), authorization (5+), report/subscription

---

## What Phase 11 Must Deliver

This work decomposes into **4 independent feature batches**. See `features/FEAT-001.json` through `FEAT-004.json` for step-by-step detail. Summary:

---

- [ ] **FEAT-001 — UI/UX Enhancements**

  Add persuasion triggers (scarcity counter, urgency countdown, Before/After transformation, "Novo" badges, "Em breve" section, authority seals) to `resources/views/landing/home.blade.php`.
  Enhance dashboard cards with explicit glassmorphism (`backdrop-filter: blur`, `bg-white/10` translucency) on the hero balance card.
  Apply Neumorphism on calculator widget inputs (soft inset shadow + light/dark surface match).
  Add systematic Alpine.js tooltip pattern (`x-tooltip`) to CRUD form fields across wallet/transaction/goal/budget views.
  Add contextual help `?` popover buttons on wallet, transaction create/edit, budget, goal forms.
  Add descriptions, examples, and guidance text to all CRUD form fields that currently lack hints.

  **Files:** `resources/views/landing/home.blade.php`, `resources/views/dashboard/index.blade.php`, `resources/views/wallets/create.blade.php`, `resources/views/wallets/edit.blade.php`, `resources/views/transactions/create.blade.php`, `resources/views/transactions/edit.blade.php`, `resources/views/goals/create.blade.php`, `resources/views/goals/edit.blade.php`, `resources/views/budgets/index.blade.php`, `resources/views/calculators/*.blade.php`, `resources/views/simulators/*.blade.php`, `resources/css/app.css`

  **Verify:** `composer test` passes (PagesSmokeTest covers all app pages); `npm run build` succeeds.

---

- [ ] **FEAT-002 — Security & Code Quality**

  Add `declare(strict_types=1)` to all 8 Auth controllers that currently lack it (AuthenticatedSessionController, RegisteredUserController, ConfirmablePasswordController, EmailVerificationNotificationController, EmailVerificationPromptController, PasswordController, PasswordResetLinkController, NewPasswordController, VerifyEmailController, ResendVerificationController, ExpiredPasswordController).
  Add `declare(strict_types=1)` to `WalletController`, `GoalController`, `SimulatorController` and any other controllers missing it.
  Extract inline validation in `WalletController::payInvoice()` to `app/Http/Requests/PayInvoiceRequest.php`.
  Add `x-ui.honeypot` to `resources/views/auth/login.blade.php` and validate `website`/`website_time` fields in `LoginRequest`.
  Add BreadcrumbList JSON-LD via `@push('jsonld')` on `resources/views/pages/privacy.blade.php`, `terms.blade.php`, `cookies.blade.php`, and `faq.blade.php`.
  Extend `StructuredDataService::breadcrumbs(array $items): array` method and register binding.

  **Files:** `app/Http/Controllers/Auth/*.php`, `app/Http/Controllers/WalletController.php`, `app/Http/Controllers/GoalController.php`, `app/Http/Controllers/SimulatorController.php`, `app/Http/Requests/PayInvoiceRequest.php` (new), `app/Interfaces/Services/StructuredDataServiceInterface.php`, `app/Services/StructuredDataService.php`, `resources/views/auth/login.blade.php`, `resources/views/pages/privacy.blade.php`, `resources/views/pages/terms.blade.php`, `resources/views/pages/cookies.blade.php`, `resources/views/pages/faq.blade.php`

  **Verify:** `composer test` · `composer lint` (Pint passes) · `composer analyse` (PHPStan level 6 passes).

---

- [ ] **FEAT-003 — Automated Tests**

  Add Feature tests:
  - `tests/Feature/WalletCrudTest.php` — create, update, delete wallet; Free-plan limit enforcement; PayInvoiceRequest validation
  - `tests/Feature/GoalCrudTest.php` — CRUD for goals; authorization (another user's goal returns 404)
  - `tests/Feature/CategoryBudgetCrudTest.php` — categories CRUD; budget store/update/delete/copyPrevious
  - `tests/Feature/ReportExportTest.php` — CSV and PDF export require Professional plan; email report throttle
  - `tests/Feature/ApiV1Test.php` — auth token create/delete; authenticated CRUD for wallets, transactions, goals via REST API v1 endpoints
  - `tests/Feature/Security/CsrfProtectionTest.php` — POST without CSRF returns 419
  - `tests/Feature/Security/RateLimitTest.php` — 6th login attempt within 1 minute gets 429
  - `tests/Feature/Security/HoneypotTest.php` — POST register with website field filled → rejected (422); POST login with website field filled → rejected
  - `tests/Feature/Security/XssInjectionTest.php` — script tag in transaction description is stored escaped; malformed SQL in search param does not error (500)
  - `tests/Feature/CalculatorSmokeTest.php` — all 7 calculator pages + 6 simulator pages return 200

  Add Unit tests:
  - `tests/Unit/Services/TransactionServiceTest.php` — installment penny split, recurring anchor, getSummary
  - `tests/Unit/Services/BudgetServiceTest.php` — getAlerts threshold; copyPrevious
  - `tests/Unit/Services/GoalServiceTest.php` — progress percent; completion status
  - `tests/Unit/Helpers/UtmTest.php` — UTM pass-through; unknown params stripped

  **Files:** All new under `tests/Feature/` and `tests/Unit/Services/`

  **Verify:** `composer test` — all new tests pass with zero failures.

---

- [ ] **FEAT-004 — Documentation & Cleanup**

  Update `README.md`: replace the Phase 10 architecture table with Phase 11 additions; add "Phase 11 highlights" section with links to new docs.
  Create `docs/maintenance/phase-11-ui-refactor.md` documenting: persuasion triggers added, Glassmorphism/Neumorphism/Aurora changes, BreadcrumbList JSON-LD pages, strict_types audit, new FormRequest, new tests added, cleanup actions.
  Orphan audit: scan `resources/views/` for `.blade.php` files not referenced by any route, `@include`, `@extends`, `x-` component or Filament resource; delete confirmed orphans.
  `public/` audit: delete any `.css`/`.js`/font files NOT produced by `npm run build` and not explicitly referenced by a Blade file (non-Vite leftovers).
  `composer.json` / `package.json` audit: list every `require` and `devDependencies` package; remove any that are demonstrably unused (no code references them); `laravel/pao` is a dev tool, verify it is used.

  **Files:** `README.md`, `docs/maintenance/phase-11-ui-refactor.md` (new), possibly several deleted orphan views/assets

  **Verify:** `composer test` still passes; `npm run build` succeeds; `composer lint` passes.

---

## Digest

| File | Block | Test | Verified |
|---|---|---|---|
| `app/Http/Controllers/Auth/RegisteredUserController.php` | `store()` — honeypot logic | `RegistrationTest`, `HoneypotTest` (new) | Existing auth tests pass |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | add `declare(strict_types=1)` | `AuthenticationTest` | Existing tests pass |
| `app/Http/Controllers/WalletController.php` | `payInvoice()` → extract to `PayInvoiceRequest` | `WalletCrudTest` (new), `WalletAccessTest` | `PagesSmokeTest`, `WalletAccessTest` pass |
| `app/Http/Requests/PayInvoiceRequest.php` | new class, full validation rules from `payInvoice()` | `WalletCrudTest` (new) | — |
| `app/Services/StructuredDataService.php` | add `breadcrumbs(array $items): array` method | `PublicPagesTest` | — |
| `app/Interfaces/Services/StructuredDataServiceInterface.php` | add `breadcrumbs()` to interface | `PublicPagesTest` | — |
| `resources/views/landing/home.blade.php` | add persuasion triggers sections | `PagesSmokeTest` smoke (home 200), `PublicPagesTest` | Home returns 200 |
| `resources/views/dashboard/index.blade.php` | glassmorphism on balance hero | `PagesSmokeTest` | Dashboard returns 200 |
| `resources/views/pages/privacy.blade.php` | `@push('jsonld')` BreadcrumbList | `PublicPagesTest` | Privacy 200 |
| `resources/views/auth/login.blade.php` | add `<x-ui.honeypot />` | `HoneypotTest` (new) | Login 200 |
| `resources/css/app.css` | neumorphism `.nm-input` / `.nm-button` utility classes | `npm run build` | Build passes |
| `README.md` | Phase 11 section | — | — |
| `docs/maintenance/phase-11-ui-refactor.md` | new file | — | — |
| **Build command** | `npm run build` | — | Must complete with zero errors |
| **Test command** | `composer test` (`php artisan optimize:clear && php artisan test`) | All test files | Must pass green |
