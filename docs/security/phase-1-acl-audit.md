# Phase 1 — ACL and per-user data isolation audit

> Audited: Phase 1 branch, 2026-09 · Laravel 13 · PHP 8.3+ · `spatie/laravel-permission` 8.3.2

Scope: every authenticated route of Finestro (`php artisan route:list --except-vendor`, routes with the `auth` middleware), the controller action behind it, and the service/repository methods it uses to read or write user-owned data (wallets, transactions, goals, budgets, categories, bills, reports/exports, receipts, AI credentials and analyses, subscription/payment history, settings/profile).

Method: read-only review of `routes/web.php`, `routes/auth.php`, every controller in `app/Http/Controllers`, the Form Requests, `app/Services`, `app/Repositories` and `App\Traits\BelongsToUser`; then fixes; then feature tests in `tests/Feature/Authorization` that exercise every owned route over HTTP with two users.

## 1. Isolation mechanisms in place

| Mechanism | Where | Effect |
|---|---|---|
| `BelongsToUser` global scope `user` | Wallet, Transaction, Goal, Budget, Category | Every Eloquent query adds `where <table>.user_id = Auth::id()` while a user is authenticated. Foreign ids resolve to `null`. |
| Scoped lookup in controller (`getXById($id) ?? abort(404)`) | all record actions | Controllers take `string $id` (no route-model binding); a foreign id is a 404 before anything else runs. |
| Policies (`app/Policies`) | Wallet, Transaction, Goal, Budget, Category | Combine ownership with a Spatie permission. Called with `$this->authorize()` in every record action and in `viewAny`/`create`. |
| Form Request `authorize()` | `Store{Wallet,Transaction,Goal,Category,Budget}Request` | `can('create', Model::class)` → 403 without the create permission. |
| Owner-scoped FK validation | `TransactionRules::ownedWalletRule()/ownedCategoryRule()`, `StoreBudgetRequest`, `WalletController::payInvoice` | `Rule::exists(...)->where('user_id', auth id)`: another user's wallet/category id is a validation error. |
| Route middleware | `permission:reports.view`, `permission:reports.export`, `permission:ai.use`, `can:viewAny,App\Models\Transaction` | 403 without the permission. |
| Explicit `user_id` queries | Subscription, PaymentHistory, AiCredential repositories | Queried by `$user->id`, never by a request id. |

## 2. 403 vs 404 convention

- A record that exists but belongs to another user (and the user is not an accepted member of that shared wallet) → **404 Not Found**. Existence is not disclosed. Policies return `Response::denyAsNotFound()` for ownership failures; scoped lookups `abort(404)`.
- An authenticated user acting on their **own** data, or on a list/create route, without the required Spatie permission/role → **403 Forbidden** (`Response::deny()`, Spatie middleware, Form Request `authorize()`).
- Permission is checked before ownership inside the policy, so a user without the permission gets 403 on their own record. For a foreign record the scoped lookup has already returned 404 before the policy runs.
- Admins do **not** bypass ownership (no `Gate::before`): an admin gets 404 on another user's wallet (`RolePermissionTest::test_admin_does_not_bypass_ownership`).
- Guests are redirected to `login` (`GuestAccessTest`).

## 3. Roles and permissions

Package: `spatie/laravel-permission` ^8.3 (8.3.2 installed), guard `web`, default config. Seeder: `Database\Seeders\RolesAndPermissionsSeeder` (idempotent; `findOrCreate` + `syncPermissions`; maps the legacy `users.role` column only for users that have no role yet).

The 24 distinct permissions below are the single source of truth: `RolesAndPermissionsSeeder::PERMISSIONS`. `admin` receives all 24; `user` receives all except `admin.access`.

| Permission | admin | user |
|---|---|---|
| `wallets.view` `wallets.create` `wallets.update` `wallets.delete` | ✔ | ✔ |
| `transactions.view` `transactions.create` `transactions.update` `transactions.delete` | ✔ | ✔ |
| `goals.view` `goals.create` `goals.update` `goals.delete` | ✔ | ✔ |
| `budgets.view` `budgets.create` `budgets.update` `budgets.delete` | ✔ | ✔ |
| `categories.view` `categories.create` `categories.update` `categories.delete` | ✔ | ✔ |
| `reports.view` `reports.export` | ✔ | ✔ |
| `ai.use` | ✔ | ✔ |
| `admin.access` | ✔ | — |

Permissions are about ACL, not plans. Plan limits (`User::hasFeature`, `User::canCreate`, `CheckPlanLimit`) are unchanged and still run inside the controllers (Phase 2 owns billing).

Policy abilities → permissions: `viewAny`/`view` → `<prefix>.view`, `create` → `.create`, `update` → `.update`, `delete`/`restore` → `.delete`; `TransactionPolicy::pay/unpay` → `transactions.update`, `receipt` → `transactions.view`; `WalletPolicy::invoice` → owner + `wallets.view`, `payInvoice` → owner + `wallets.update`; `WalletPolicy::view` → `wallets.view` + (owner **or** accepted member).

Middleware aliases (`bootstrap/app.php`): `role` → `Spatie\Permission\Middleware\RoleMiddleware` (replaces the deleted `App\Http\Middleware\CheckRole`; no route used it), `permission`, `role_or_permission`. New registrations get role `user` (`RegisteredUserController::store`).

## 4. Route audit

Status: **OK** = scoped before this phase; **GAP** = defect found (isolation, ACL or layering). Every row now has the policy/permission check listed under "fix applied".

Qualifiers on `GAP`: **(ACL)** = missing authorization check (policy/permission/Form Request); **(layer)** = business logic or a raw query that belonged in a service/repository; **(ACL + layer)** = both. A bare **GAP** is an isolation defect (foreign data reachable, 500, or a fake success).

| Route | Controller@method | Scoping mechanism | Status | Fix applied |
|---|---|---|---|---|
| `dashboard` | DashboardController@index | Services/repositories under BelongsToUser scope | OK | none (test: TransactionAccessTest listings) |
| `wallets.index` | WalletController@index | Own list: scoped `getAll`. Shared list: **raw** `Wallet::withoutGlobalScope('user')->whereHas('members')` query in controller | GAP (layer) | Query moved to `WalletRepository::getSharedWithUser` via `WalletService::getSharedWallets`; `authorize('viewAny')` |
| `wallets.create` | WalletController@create | n/a (form) | GAP (ACL) | `authorize('create', Wallet)` |
| `wallets.store` | WalletController@store | BelongsToUser `creating` sets `user_id` | GAP (ACL) | `StoreWalletRequest::authorize()` → `can('create')` |
| `wallets.show` | WalletController@show | Scoped lookup → 404; accepted shared-wallet members also got 404 | GAP | `WalletService::getAccessibleWallet` (owner or accepted member, `WalletRepository::findAccessibleById`) + `authorize('view')`; invoice summary only when `can('invoice')`; owner-only buttons wrapped in `@can` |
| `wallets.edit` | WalletController@edit | Scoped lookup → 404 | GAP (ACL) | `authorize('update')` |
| `wallets.update` | WalletController@update | Scoped lookup → 404 | GAP (ACL) | `authorize('update')` |
| `wallets.destroy` | WalletController@destroy | Scoped lookup → 404 | GAP (ACL) | `authorize('delete')` |
| `wallets.invoice` | WalletController@invoice | Scoped lookup → 404 | GAP (ACL) | `authorize('invoice')` (owner only) |
| `wallets.invoice.pay` | WalletController@payInvoice | Scoped lookup → 404; `source_wallet_id` `Rule::exists(...)->where('user_id')` | GAP (ACL) | `authorize('payInvoice')` (owner only) |
| `transactions.index` | TransactionController@index | BelongsToUser scope | GAP (ACL) | `authorize('viewAny', Transaction)` |
| `transactions.create` | TransactionController@create | n/a (form; wallet/category lists scoped) | GAP (ACL) | `authorize('create', Transaction)` |
| `transactions.store` | TransactionController@store | `ownedWalletRule`/`ownedCategoryRule`; receipt stored under `receipts/{own id}` | GAP (ACL) | `StoreTransactionRequest::authorize()` → `can('create')` |
| `transactions.trash` | TransactionController@trash | `getTrashed` under scope | GAP (ACL) | `authorize('viewAny', Transaction)` |
| `transactions.show` | TransactionController@show | Scoped lookup → 404 | GAP (ACL) | `authorize('view')` |
| `transactions.edit` | TransactionController@edit | Scoped lookup → 404 | GAP (ACL) | `authorize('update')` |
| `transactions.update` | TransactionController@update | Service threw `ModelNotFoundException` (404) but the uploaded receipt was stored to `receipts/{attacker}` **before** the lookup (orphan file per attempt) | GAP | Lookup `?? abort(404)` + `authorize('update')` before any file is stored |
| `transactions.destroy` | TransactionController@destroy | Scoped lookup → 404 | GAP (ACL) | `authorize('delete')` |
| `transactions.pay` | TransactionController@pay | Service lookup → `ModelNotFoundException` (404) | GAP (ACL) | `getTransactionById ?? abort(404)` + `authorize('pay')` |
| `transactions.unpay` | TransactionController@unpay | Service lookup → `ModelNotFoundException` (404) | GAP (ACL) | `getTransactionById ?? abort(404)` + `authorize('unpay')` |
| `transactions.restore` | TransactionController@restore | `findTrashedById` under scope → 404 | GAP (ACL) | New `TransactionService::getTrashedTransactionById` ?? 404 + `authorize('restore')` |
| `transactions.receipt` | TransactionController@receipt | Scoped lookup; private disk | GAP (ACL) | `?? abort(404)` + `authorize('receipt')` |
| `bills.index` | BillController@index | Scoped transaction queries | GAP (ACL) | `can:viewAny,App\Models\Transaction` route middleware |
| `budgets.index` | BudgetController@index | `getForMonth` under scope | GAP (ACL) | `authorize('viewAny', Budget)` |
| `budgets.store` | BudgetController@store | `category_id` `Rule::exists(...)->where('user_id')`; plan-limit check ran `$user->budgets()` queries in controller (`withinPlanLimit`) | GAP (ACL + layer) | `StoreBudgetRequest::authorize()` → `can('create')`; limit check moved to `BudgetService::canAddBudget` (repository `findForPeriod`/`countForMonth`) |
| `budgets.update` | BudgetController@update | Scoped lookup → 404 | GAP (ACL) | `authorize('update')` |
| `budgets.destroy` | BudgetController@destroy | Scoped lookup → 404 | GAP (ACL) | `authorize('delete')` |
| `budgets.copy-previous` | BudgetController@copyPrevious | Repository under scope | GAP (ACL) | `authorize('create', Budget)` |
| `goals.index` | GoalController@index | Scope | GAP (ACL) | `authorize('viewAny', Goal)` |
| `goals.create` | GoalController@create | n/a | GAP (ACL) | `authorize('create', Goal)` |
| `goals.store` | GoalController@store | `creating` sets `user_id` | GAP (ACL) | `StoreGoalRequest::authorize()` |
| `goals.show` | GoalController@show | Scoped lookup → 404 | GAP (ACL) | `authorize('view')` |
| `goals.edit` | GoalController@edit | Scoped lookup → 404 | GAP (ACL) | `authorize('update')` |
| `goals.update` | GoalController@update | **No lookup**: `GoalRepository::update` called `->update()` on `null` → HTTP 500 on a foreign id | GAP | `getGoalById ?? abort(404)` + `authorize('update')` |
| `goals.destroy` | GoalController@destroy | **No lookup**: delete returned false and the user got a fake "Meta excluída" success | GAP | `getGoalById ?? abort(404)` + `authorize('delete')` |
| `categories.index` | CategoryController@index | Scope | GAP (ACL) | `authorize('viewAny', Category)` |
| `categories.create` | CategoryController@create | n/a | GAP (ACL) | `authorize('create', Category)` |
| `categories.store` | CategoryController@store | `creating` sets `user_id` | GAP (ACL) | `StoreCategoryRequest::authorize()` |
| `categories.edit` | CategoryController@edit | Scoped lookup → 404 | GAP (ACL) | `authorize('update')` |
| `categories.update` | CategoryController@update | **No lookup** → HTTP 500 on a foreign id | GAP | `getCategoryById ?? abort(404)` + `authorize('update')` |
| `categories.destroy` | CategoryController@destroy | **No lookup** → fake success on a foreign id | GAP | `getCategoryById ?? abort(404)` + `authorize('delete')` |
| `categories.import-suggested` | CategoryController@importSuggested | `CategoryService::importSuggested` uses `Category::where('user_id', $user->id)` | GAP (ACL) | `authorize('create', Category)` (direct query in service left, see §5) |
| `reports.index` | ReportController@index | `getTransactionsByDateRange` (scoped); `wallet_id`/`wallet` filter applied on the scoped collection | OK (data) / GAP (ACL) | `permission:reports.view` |
| `reports.export` | ReportController@export | Same as index; plan gate `hasFeature('reports_export')` | OK (data) / GAP (ACL) | `permission:reports.export` |
| `reports.pdf` | ReportController@pdf | Same | OK (data) / GAP (ACL) | `permission:reports.export` |
| `reports.print` | ReportController@print | Same | OK (data) / GAP (ACL) | `permission:reports.export` |
| `reports.email` | ReportController@email | Same; recipient is always `$request->user()` | OK (data) / GAP (ACL) | `permission:reports.export` |
| `ai.index` | AiController@index | `$user->aiCredential` | GAP (ACL) | `permission:ai.use` (plan gate kept) |
| `ai.settings` | AiController@settings | `$user->aiCredential` | GAP (ACL) | `permission:ai.use` |
| `ai.credentials.save` | AiController@saveCredentials | `AiCredential::updateOrCreate(['user_id' => $user->id])` directly in controller | GAP (ACL + layer) | `permission:ai.use`; moved to `AiCredentialRepository::updateOrCreateForUser` via `AiService::saveCredentials` with `AiCredentialDTO` |
| `ai.analyze.finances` | AiController@analyzeFinances | `AiService::buildFinancialContext` uses `$user->wallets()/transactions()/goals()` | OK (data) / GAP (ACL) | `permission:ai.use` |
| `ai.analyze.goals` | AiController@analyzeGoals | Same | OK (data) / GAP (ACL) | `permission:ai.use` |
| `ai.generate.plan` | AiController@generatePlan | Same | OK (data) / GAP (ACL) | `permission:ai.use` |
| `ai.technical.report` | AiController@technicalReport | Same | OK (data) / GAP (ACL) | `permission:ai.use` |
| `ai.financial.planning` | AiController@financialPlanning | Same | OK (data) / GAP (ACL) | `permission:ai.use` |
| `subscription.index` | SubscriptionController@index | `findLatestForUser($user->id)`, `getRecentForUser($user->id)` | OK | none (test: ReportAccessTest) |
| `subscription.plans` | SubscriptionController@plans | Current user's plan only | OK | none |
| `subscription.checkout` | SubscriptionController@checkout | Creates a session for `$request->user()` | OK | none (Stripe = Phase 2) |
| `subscription.success` | SubscriptionController@success | `handleCheckoutSuccess` does not verify that the Stripe session belongs to the current user (`client_reference_id`) | FIXED (Phase 2) | ~~Phase 2 must-fix.~~ Fixed: session must be `complete`, owned by the current user (`client_reference_id`/`metadata.user_id`) and match the stored customer, else 404 with no writes. See [Phase 2 billing](../billing/phase-2-stripe.md#security-fixes). Test: `tests/Feature/Billing/CheckoutSuccessTest.php` |
| `subscription.cancel` | SubscriptionController@cancel | Current user's subscription | OK | none |
| `subscription.test-guide` | Closure | Static page | OK | none |
| `calculators.*` (index, retirement, currency, thirteenth-salary, vacation, financing, investment, interest) | CalculatorController@* | No stored user data | OK | none |
| `simulators.*` (index, retirement, investment, budget, financial-profile, investor-profile) | SimulatorController@* | No stored user data; plan gate unchanged | OK | none |
| `settings.index` | SettingController@index | `Auth::user()` | OK | none |
| `settings.password` | SettingController@updatePassword | `Auth::user()->update()` | OK | none (direct model update, Breeze style, see §5) |
| `settings.account.delete` | SettingController@deleteAccount | `Auth::user()->delete()` | OK | none |
| `profile.edit` / `profile.update` / `profile.destroy` | ProfileController@edit/update/destroy | `$request->user()` (Breeze) | OK | none |
| `terms.accept` / `terms.store` | Closures | `Auth::user()` | OK | none |
| `password.confirm` (GET/POST), `password.update`, `verification.notice`, `verification.verify`, `verification.send`, `logout` | Breeze auth controllers | Current user only (signed URL for verify) | OK | none |
| `stripe.webhook` | SubscriptionController@webhook | Not `auth`; signature verification | out of scope (Phase 2) | none |

Console: `ProcessRecurringTransactions` uses `withoutGlobalScopes()` by design (no authenticated user in console); it copies each record with its own `user_id`. OK.

## 5. Explicit exceptions and items left for later phases

- **Spatie tables keep bigint ids** (`roles`, `permissions`, pivots) and have no SoftDeletes: they are framework lookup tables like `jobs`/`cache`. Only `model_has_roles.model_id` / `model_has_permissions.model_id` were changed to `uuid` to reference UUID users (`database/migrations/2026_09_30_000100_create_permission_tables.php`).
- **Legacy `users.role` column** is kept (readable, still fillable, default `user`) for backward compatibility. Authorization no longer reads it; the seeder maps it once.
- **`subscription.success` session ownership** → Phase 2 (Stripe). **Fixed in Phase 2**, see [docs/billing/phase-2-stripe.md](../billing/phase-2-stripe.md#security-fixes).
- **Shared wallets**: accepted members can only *view* the wallet (`wallets.show`) and see it under "Carteiras Compartilhadas Comigo". Editing, deleting, invoices and posting transactions stay owner-only. Showing the owner's transactions to members, and `WalletMember` editor/admin rights, are deferred. Members currently see only their own entries in that wallet, because transactions keep the `BelongsToUser` scope.
- **Update Form Requests** (`Update{Wallet,Transaction,Goal,Category}Request`, `StoreBudgetRequest` on PUT) keep `authorize() = true`; the controller authorizes after the scoped lookup. Validation therefore runs first. This leaks nothing because every FK rule in those requests is owner-scoped.
- **`CategoryService::importSuggested`** queries `Category::where('user_id', $user->id)` directly instead of through the repository. It is explicitly scoped; the layering nit is left for a later refactor.
- **Settings/Profile** update `Auth::user()` directly in the controller (Breeze style). Scoped to the current user by construction; left as is.
- Out of scope by user decision: Stripe/billing and `CheckPlanLimit` behaviour, Pest/PHPStan/Larastan/Pint/Rector, LGPD pages, SEO, landing/CTA, Livewire.

## 6. Tests that prove each row

| Test file | Covers |
|---|---|
| `tests/Feature/Authorization/WalletAccessTest.php` | 404 for B on A's `wallets.show/edit/update/destroy/invoice/invoice.pay`; index does not list A's wallet; accepted member 200 on show and listed on index, 404 on edit/update/destroy/invoice; pending member and stranger 404; owner full access; B cannot use A's wallet as `source_wallet_id` |
| `tests/Feature/Authorization/TransactionAccessTest.php` | 404 on `transactions.show/edit/update/destroy/pay/unpay/receipt/restore`; DB and wallet balance unchanged; no receipt file stored for a foreign update; A's data absent from `transactions.index/trash`, `bills.index`, `dashboard`; owner still works |
| `tests/Feature/Authorization/GoalCategoryBudgetAccessTest.php` | 404 (no 500, no fake success) on goals show/edit/update/destroy, categories edit/update/destroy, budgets update/destroy; A's names absent from the indexes; A's category rejected in B's transaction and budget |
| `tests/Feature/Authorization/ReportAccessTest.php` | `reports.index/print/export` never include A's data, also with `wallet_id`/`wallet` = A's wallet; `reports.pdf` 200; `reports.email` only to B; `subscription.index` hides A's payments; `ai.credentials.save` only touches B's row |
| `tests/Feature/Authorization/GuestAccessTest.php` | 54 authenticated routes redirect guests to `login` |
| `tests/Feature/Authorization/RolePermissionTest.php` | Seeder idempotent (2 roles / 24 permissions); legacy column mapping without overwriting; registration assigns `user`; user without role → 403 on own data and list routes; missing `wallets.delete` → 403; `role:admin` alias 200/403; admin gets 404 on another user's wallet; `isAdmin()` follows the Spatie role |
| `tests/Feature/DataIsolationTest.php`, `TransactionLifecycleTest.php` (existing) | Scoping regression guards (unchanged, still 404) |

## 7. Phase 6 admin panel

Phase 6 added a Filament admin panel at `/admin` (details: [docs/admin/phase-6-filament.md](../admin/phase-6-filament.md)). It does not change the isolation rules above:

- Admins still have **no ownership bypass in the user app**. No `Gate::before` and no model policies were added; `RolePermissionTest::test_admin_does_not_bypass_ownership` still passes (404 on another user's wallet).
- The panel cannot read financial content. It has resources only for users, subscriptions, payments and the activity log; no route or resource exists for wallets, transactions, goals, budgets, categories, receipts/media or AI credentials/analyses. Activity entries about financial models show only event, type and date (`tests/Feature/Admin/DataMinimisationTest.php`, `ActivityResourceTest.php`).
- Panel access requires role `admin` + permission `admin.access` + verified e-mail + not suspended, a separate `admin` session guard and mandatory TOTP MFA. Inside the panel only view abilities are allowed; account changes go through `AdminUserService` with self/last-admin guards and are logged with the admin as causer.
- New account state `users.suspended_at` blocks user-app login, ends active sessions, returns 403 on API v1 and revokes Sanctum tokens.
