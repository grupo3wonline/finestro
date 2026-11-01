# Review — Finestro Phase 1 (ACL + per-user isolation), iteration 1

Verdict: **APPROVED** (no blockers).

## Scope

Files listed in `implementation-log.md` plus `docs/security/phase-1-acl-audit.md`, checked against `plan.md` and the brief. Suites were not re-run; the coder's recorded evidence was used.

## Evidence checked

- **Spatie install**: `composer.json` has `spatie/laravel-permission: ^8.3` (8.3.2 per log). The migration `2026_09_30_000100_create_permission_tables.php` is additive and uses `uuid(model_id)` in both pivots. Log: only this migration was pending, and `migrate` ran on dev with no destructive commands.
- **Seeder** `RolesAndPermissionsSeeder`: uses `findOrCreate` and `syncPermissions`. It maps legacy `role` only for users with no roles (`whereDoesntHave('roles')`), so re-runs never overwrite roles. The log records three dev runs with stable counts (2/24, 8 assignments, 47 role_has_permissions), and `RolePermissionTest` covers idempotency and non-overwrite.
- **Roles on new users**: `RegisteredUserController::store` assigns `user`. The two `User::create` paths (registration and the DatabaseSeeder demo user) both assign a role, and `UserFactory::configure()` assigns one when missing.
- **Middleware**: in `bootstrap/app.php`, `role` now points to Spatie `RoleMiddleware`, and `permission` and `role_or_permission` were added. `plan.limit`, `terms.accepted`, SecurityHeaders and the CSRF exception are unchanged. `CheckRole` was deleted; no route referenced it. A test route proves the `role:admin` alias.
- **Policies**: `OwnedResourcePolicy` checks the permission first (403) and ownership second (`denyAsNotFound`, 404). `WalletPolicy::view` allows the owner or an accepted member. `invoice` and `payInvoice` are owner-only. No `Gate::before` bypass was added. Ability names (`view`, `update`, etc.) never match permission strings, so Spatie's gate hook returns null for them, including the route middleware `can:viewAny,App\Models\Transaction`.
- **Controllers**: every record action in Wallet, Transaction, Goal, Category and Budget looks the record up through a scoped query, returns 404 on miss, then calls `authorize()`.
  - `transactions.update` now authorizes before storing the receipt.
  - `goals.update`/`destroy` and `categories.update`/`destroy` no longer return a 500 or a fake success.
  - `restore` uses the new `getTrashedTransactionById`.
  - `wallets.show` uses `findAccessibleById`, which still applies the SoftDeletes scope and requires `accepted_at`.
  - Store requests authorize `create`.
- **Layering**:
  - The shared-wallet query moved to `WalletRepository::getSharedWithUser`.
  - AI credentials moved into a repository, a DTO and the service, with the binding added in `AppServiceProvider`. The DTO keeps nulls, which matches the old behaviour because `ai/settings.blade.php` always submits all three key fields. Nothing calls `new AiService` directly, so the constructor change is safe.
  - The budget limit check moved to `BudgetService::canAddBudget`.
- **Blade**: `wallets/show` gained `@can` wraps and `wallets/index` gained the shared-wallet name link. Both are targeted edits with no text or operator changes.
- **Routes**: the reports group now requires `permission:reports.view`. Export, pdf, print and email also require `permission:reports.export`, and the existing throttle is kept. The AI group requires `permission:ai.use`. Stripe and subscription routes are untouched.
- **Tests** (`tests/Feature/Authorization/*`):
  - Cross-user 404s on every owned route: wallets, including invoice and invoice.pay; transactions, including pay, unpay, restore and receipt; goals; categories; budgets.
  - No orphan receipt file after a foreign update.
  - Listings stay isolated: index, trash, bills, dashboard, the goals/categories/budgets indexes, and reports index/print/export/pdf/email with a foreign `wallet_id`.
  - Shared wallets: an accepted member can view; pending members and strangers get 404; members get 404 on manage routes.
  - Guest redirects across 54 routes.
  - Role and permission checks: 403 without a role or permission, and an admin gets 404 on another user's records.
  - Tests use the base `TestCase` seeding against SQLite `:memory:`.
- **Recorded results**: full suite 188 passed (583 assertions), up from a 107-test baseline. `route:list` OK (112 routes). `view:cache` OK. `php -l` clean on 49 files.
- **Audit doc**: lists every authenticated route. Every GAP row names its fix. The one justified exception is `subscription.success` (Stripe session ownership), deferred to Phase 2 by user decision. The doc states the 404-for-foreign / 403-for-missing-permission convention.

## Notes (non-blocking, already documented as accepted trade-offs)

- Update Form Requests validate before the ownership check (plan Decision 7). An intruder who sends invalid data gets 302 with validation errors instead of 404. All FK rules are owner-scoped, so nothing leaks.
- `subscription.success` session ownership must be fixed in Phase 2.
