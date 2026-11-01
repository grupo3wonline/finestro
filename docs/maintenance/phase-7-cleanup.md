# Phase 7: Orphan cleanup

Legacy files left over from the Breeze scaffold and earlier phases were removed
after proving that nothing references them. All deleted source files exist in the
git history (`HEAD`), so the temporary backup folder was discarded afterwards.

## Evidence

Searches ran over `app/`, `resources/` (views and `js/`), `routes/`, `config/`,
`tests/`, `database/` and `bootstrap/` (plus the whole project outside
`vendor/`, `node_modules/`, `storage/`, `.agents/` and `public/build/`):

| Search | Result |
| --- | --- |
| `<x-(app-layout\|guest-layout\|application-logo\|nav-link\|responsive-nav-link\|dropdown\|dropdown-link\|primary-button\|secondary-button\|danger-button\|text-input\|input-label\|input-error\|modal\|auth-session-status)\b` | Only inside the deleted files (`dashboard.blade.php`, `layouts/navigation.blade.php`). `profile/*` uses none of them. |
| `components\.(app-layout\|...\|auth-session-status)\b` (`@include` form) | No match |
| `view('welcome')`, `view('dashboard')`, `'welcome'`, `layouts.navigation` | No match (the app dashboard is `dashboard/index.blade.php`) |
| `AppLayout`, `GuestLayout`, `App\View\`, `View/Components`, `Blade::component`, `dynamic-component` | Only the two class files themselves |
| `temp-laravel`, `welcome.blade`, `navigation.blade`, `phpunit.result.cache` | Only `.gitignore` (for the cache file) |
| Directories named `bkp*`, `backup*`, `bak*`, `old*` and files `*.bak`, `*.old`, `*.orig`, `*~`, `*.tmp`, `*.swp` | None found |
| `pages.(about\|services\|tools)`, `route('about'\|'services'\|'tools')`, `freshdesk` | No match; the `about/services/tools` route names stay as 301 redirects |
| `lang/` keys for the removed pages | None (`lang/pt_BR` has only auth, pagination, passwords, validation) |

`input-label` and `input-error` also appear as **CSS classes** (`class="input-label"`)
in many views and in `<x-ui.field>`; those are Tailwind component classes from
`resources/css`, not the deleted Blade components, and were kept.

## Removed

| Path | Size (bytes) | Result |
| --- | ---: | --- |
| `resources/views/welcome.blade.php` | 72302 | Deleted |
| `resources/views/dashboard.blade.php` (root, Breeze) | 513 | Deleted |
| `resources/views/layouts/navigation.blade.php` | 5013 | Deleted |
| `resources/views/components/app-layout.blade.php` | 1497 | Deleted |
| `resources/views/components/guest-layout.blade.php` | 1375 | Deleted |
| `resources/views/components/application-logo.blade.php` | 3045 | Deleted |
| `resources/views/components/nav-link.blade.php` | 605 | Deleted |
| `resources/views/components/responsive-nav-link.blade.php` | 688 | Deleted |
| `resources/views/components/dropdown.blade.php` | 1244 | Deleted |
| `resources/views/components/dropdown-link.blade.php` | 217 | Deleted |
| `resources/views/components/primary-button.blade.php` | 404 | Deleted |
| `resources/views/components/secondary-button.blade.php` | 393 | Deleted |
| `resources/views/components/danger-button.blade.php` | 380 | Deleted |
| `resources/views/components/text-input.blade.php` | 184 | Deleted |
| `resources/views/components/input-label.blade.php` | 143 | Deleted |
| `resources/views/components/input-error.blade.php` | 241 | Deleted |
| `resources/views/components/modal.blade.php` | 3205 | Deleted |
| `resources/views/components/auth-session-status.blade.php` | 160 | Deleted |
| `app/View/Components/AppLayout.php` | 296 | Deleted |
| `app/View/Components/GuestLayout.php` | 300 | Deleted |
| `app/View/Components/`, `app/View/` | empty after the above | Deleted |
| `temp-laravel/` | empty directory | Deleted |
| `.phpunit.result.cache` | 41578 | Deleted (git-ignored cache; may be recreated by test runs) |

## Second pass (2026-11-01)

A scan of every Blade view, every class under `app/`, and the npm and Composer
dependencies found no further orphan code. Classes without a textual reference
(Artisan commands, Policies, `App\OpenApi\*`, Filament widgets) are discovered
by the framework and are in use. The pass removed only generated or redundant
files:

| Path | Reason |
| --- | --- |
| `.styleci.yml` | StyleCI is not used; formatting is Pint (`pint.json`) in `quality.yml`. |
| `.agents/tasks/phase-7-final/deleted-backup/` | Duplicate of files kept in git history. |
| `.agents/tasks/*/task-*/` | Workflow engine state (`context.json`, `task.json`, `features/`). Plans, reviews and verdicts stay. |
| `.agents/tasks/phase-3-quality/*.txt`, `phase-6-5-security/*.log`, `audit-after.json`, `audit-summary.php`, `lock-diff.php` | Raw command output. |
| `storage/logs/p4-*.txt`, `storage/logs/laravel.log`, `storage/framework/testing/` | Git-ignored runtime output. |

## Kept on purpose

- `database/sql/*.sql`: MySQL views and procedures required by the project
  structure; they are applied manually.
- `.agents/tasks/phase-6-5-security/backup/`: rollback files documented in
  `docs/security/phase-6-5-dependency-update.md`.
- `.agents/tasks/phase-7-final/tools/make-images.php`: regenerates the social
  and PWA images (see `docs/seo/phase-7-seo.md`).

- `docs/INTEGRACAO-SISTEMAS-LEGADOS.md`: documentation of the earlier
  integration, still linked from the README.
- `resources/views/profile/*`: used by `ProfileController` (`/profile`).
- `.env`, `docs/`, `storage/app` data, uploads, migrations, `vendor/`,
  `node_modules/`: never touched.

## Verification after deletion

`php artisan view:cache` (all templates compile), `php artisan view:clear`,
`composer dump-autoload` (no missing classes), then the full `php artisan test`
run reported in [phase-7-tests.md](../testing/phase-7-tests.md).

## Restoring a file

```powershell
git restore --source=HEAD -- resources/views/welcome.blade.php
```

