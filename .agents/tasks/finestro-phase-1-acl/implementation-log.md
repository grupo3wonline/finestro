# Implementation log — Finestro Phase 1 (ACL + per-user isolation)

## Iteration 1 — implementation of plan.md (no review.json yet)

Worked in place, nothing committed/stashed/reset.

### Files created

- config/permission.php (Spatie publish, defaults)
- database/migrations/2026_09_30_000100_create_permission_tables.php (published stub, renamed; `model_id` → `uuid` in model_has_permissions and model_has_roles)
- database/seeders/RolesAndPermissionsSeeder.php
- app/Policies/OwnedResourcePolicy.php, WalletPolicy.php, TransactionPolicy.php, GoalPolicy.php, BudgetPolicy.php, CategoryPolicy.php
- app/DTOs/AiCredentialDTO.php
- app/Interfaces/Repositories/AiCredentialRepositoryInterface.php
- app/Repositories/AiCredentialRepository.php
- tests/Feature/Authorization/WalletAccessTest.php, TransactionAccessTest.php, GoalCategoryBudgetAccessTest.php, ReportAccessTest.php, GuestAccessTest.php, RolePermissionTest.php
- docs/security/phase-1-acl-audit.md
- .agents/tasks/finestro-phase-1-acl/implementation-log.md (this file)

### Files changed

- composer.json, composer.lock (spatie/laravel-permission ^8.3, installed 8.3.2)
- bootstrap/app.php (`role` → Spatie RoleMiddleware; added `permission`, `role_or_permission`)
- app/Models/User.php (HasRoles; removed custom `hasRole`; `isAdmin()` uses Spatie; legacy `role` docblock)
- app/Models/Wallet.php (`isOwnedBy`, `hasAcceptedMember`)
- app/Http/Controllers/Controller.php (AuthorizesRequests)
- app/Http/Controllers/Auth/RegisteredUserController.php (assign role `user`)
- app/Http/Controllers/WalletController.php, TransactionController.php, GoalController.php, CategoryController.php, BudgetController.php, AiController.php
- app/Http/Requests/StoreWalletRequest.php, StoreTransactionRequest.php, StoreGoalRequest.php, StoreCategoryRequest.php, StoreBudgetRequest.php
- app/Interfaces/Repositories/WalletRepositoryInterface.php, app/Repositories/WalletRepository.php
- app/Interfaces/Services/WalletServiceInterface.php, app/Services/WalletService.php
- app/Interfaces/Services/AiServiceInterface.php, app/Services/AiService.php
- app/Interfaces/Services/BudgetServiceInterface.php, app/Services/BudgetService.php
- app/Interfaces/Services/TransactionServiceInterface.php, app/Services/TransactionService.php
- app/Providers/AppServiceProvider.php (AiCredential repository binding)
- routes/web.php (`permission:reports.view` group, `permission:reports.export` on export/pdf/print/email, `permission:ai.use` group, `can:viewAny,App\Models\Transaction` on bills.index)
- resources/views/wallets/show.blade.php (targeted `@can` wraps around invoice/Lançar/Editar/edit hint)
- resources/views/wallets/index.blade.php (shared wallet name links to wallets.show)
- database/seeders/DatabaseSeeder.php (calls RolesAndPermissionsSeeder; demo user gets role `user`)
- database/factories/UserFactory.php (`configure()` assigns role from legacy column; `admin()` state)
- tests/TestCase.php (`$seed = true`, `$seeder = RolesAndPermissionsSeeder::class`)

### Files deleted

- app/Http/Middleware/CheckRole.php (no route used it)

### Commands and results

| Command | Result |
|---|---|
| `php artisan test` (baseline before changes) | 107 passed (318 assertions) |
| `composer require spatie/laravel-permission:^8.3` | Installed, but PowerShell→composer.bat stripped `^` and wrote constraint `"8.3"` (8.3.0). Fixed composer.json to `"^8.3"` and ran `composer update spatie/laravel-permission` → 8.3.2, exit 0. (Composer reports 41 pre-existing security advisories in other packages; not touched.) |
| `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"` | config/permission.php + migration published |
| `php artisan config:clear`; `php artisan migrate:status` | only `2026_09_30_000100_create_permission_tables` Pending |
| `php artisan migrate` (dev DB, additive) | `2026_09_30_000100_create_permission_tables` DONE |
| `php artisan db:seed --class=RolesAndPermissionsSeeder` run 1 | OK → roles/permissions 2/24, 8 users, 8 role assignments, 0 admins (no legacy admin rows) |
| `php artisan db:seed --class=RolesAndPermissionsSeeder` run 2 (idempotency) | OK → 2/24, 8 assignments, 47 role_has_permissions (24 + 23), unchanged |
| `php artisan test` after steps 1–3 | 107 passed (318 assertions) |
| `php artisan test` after steps 4–7 | 107 passed (318 assertions) |
| `php artisan test tests/Feature/Authorization` | 81 passed (265 assertions) |
| `php -l` on 49 changed/created PHP files | 0 errors |
| `php artisan optimize:clear` | OK |
| `php artisan route:list` | OK, 112 routes |
| `php artisan view:cache` (then `view:clear`) | Blade templates cached successfully |
| `php artisan db:seed --class=RolesAndPermissionsSeeder` run 3 (final) | OK → 2/24, 8 assignments, 47 role_has_permissions |
| `php artisan migrate:status` | permission migration Ran, nothing pending |
| `php artisan test` (final, full suite) | **188 passed (583 assertions)**, 0 failures |

Tests run against SQLite `:memory:` (phpunit.xml); the dev MySQL DB was only touched by the additive migration and the roles seeder.
