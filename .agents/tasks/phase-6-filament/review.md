# Filament v5 admin panel at /admin, account suspension, and Phase 5 API fixes (review pass 2)

Phase 6 adds a Filament v5.11 / Livewire v4.4.8 admin panel at `/admin` on its own `admin` session guard. Entry requires role `admin`, permission `admin.access`, a verified e-mail, no suspension, and TOTP MFA with recovery codes. The panel has four read-mostly resources (users, subscriptions, payments, activity) and a dashboard. Writes go through `AdminUserService`, which reuses `AccountDeletionService` and logs to `SecurityActivityLogger` with the admin as causer. The diff also fixes the three Phase 5 findings: an IP limiter before `auth:sanctum`, a subquery-based Scout search, and a wallet update contract that matches store. This pass checks the fixes for the seven pass-1 findings. All seven are resolved in code, tests and docs. Iteration-2 evidence in verification-log.md: `composer quality` exits 0, and the suite passes in normal and random order (seed 1793498445) at 852 passed / 15 skipped / 3688 assertions, up from 672 / 15 / 2734. The 15 skips are the documented ApiEndpointMatrix skips. The PHPStan baseline still has 3 entries, and all build/cache commands exit 0.

Watch for: the formula guard also adds the apostrophe to XLSX cells, where it shows as a literal character (**likely**, cosmetic, non-blocking).

**Verdict**: APPROVED

## High-level view

The CSV formula-injection blocker is fixed. A small `EscapesFormulaInjection` trait prefixes `'` to name, e-mail, currency and description values that start with `=`, `+`, `-`, `@`, tab or CR. A test covers HYPERLINK, `+` and `@` payloads, and docs §8 now describes the real behaviour. The guard runs for both writers, so XLSX cells (already typed as strings by `StringValueBinder`) also get a visible leading apostrophe.

The wallet update now distinguishes "omitted" from "cleared". `WalletDTO::forUpdate()` records which of the five clearable columns arrived as explicit nulls, and `toArray()` keeps those nulls. Web and API update controllers both use it. Every other field still treats null as "unchanged", matching the OpenAPI `WalletUpdateInput` description and docs/api/phase-5-api.md. The user's condition (clear only where store allows null) holds: `StoreWalletRequest` makes all five fields nullable for every wallet type, and a card without days is an existing supported state.

The remaining pass-1 items are closed. `TransactionMonthFilterTest` pins the clock to four month-ends, including both Februaries and year end, and checks `inMonth()`, the list and the summary. `AdminAccessTest` now runs guest, plain user, no-permission, unverified, suspended and MFA admin against every resource index and view URL. `promote()` applies the same verified/not-suspended rules as `finestro:make-admin` through shared constants. Docs §5/§6 tell operators to finish MFA set-up personally and right away. The stray `*.txt` files are gone from the root.

<details>
<summary>Issues (1)</summary>

1. **Apostrophe visible in XLSX export (non-blocking)**: `AdminPaymentsExport::text()` escapes for both formats, but XLSX cells are already strings, so the `'` becomes part of the visible value. Optionally apply the prefix only for the CSV writer. No action is needed to ship.

</details>

<details>
<summary>Details</summary>

## Formula-injection fix in the payments export

`map()` now routes user-controlled text through the trait:

```php
$this->text($row->user->name ?? ''),
$this->text($row->user->email ?? ''),
...
$this->text($row->description),
```

In the CSV path a spreadsheet treats the leading `'` as a text marker. In the XLSX path `StringValueBinder` already stored the cell as a string, so the apostrophe is part of the value and is shown (**likely**; PhpSpreadsheet stores a quote prefix as a style flag, not as content, and the export writes it as content).

## Test coverage

Not tested: Filament/Livewire under the CSP in a real browser (`AdminCspTest` matches the external origins in the `/admin/login` HTML against the header). XLSX rendering of escaped cells (the test asserts mapped values, not the written file).

</details>

<details>
<summary>File map</summary>

Phase 6 files only. The working tree also holds Phases 1-5; most Phase 6 files are untracked, so read them directly (`git -C <root> status`, `git diff HEAD`).

Changed in pass 2:

- `app/Exports/Concerns/EscapesFormulaInjection.php` (new), `app/Exports/AdminPaymentsExport.php`: formula guard on user text.
- `app/DTOs/WalletDTO.php`: `CLEARABLE_ON_UPDATE`, `forUpdate()`, `cleared` list kept by `toArray()`.
- `app/Http/Controllers/WalletController.php`, `app/Http/Controllers/Api/V1/WalletController.php`: update uses `WalletDTO::forUpdate()`.
- `app/OpenApi/RequestSchemas.php`, `storage/api-docs/*`: `WalletUpdateInput` describes null-clears.
- `app/Services/AdminUserService.php`, `app/Interfaces/Services/AdminUserServiceInterface.php`, `app/Console/Commands/MakeAdmin.php`: shared promote preconditions and messages.
- `tests/Feature/TransactionMonthFilterTest.php` (new), `tests/Feature/Admin/AdminAccessTest.php`, `tests/Feature/Admin/BillingResourcesTest.php`, `tests/Feature/Admin/AdminUserServiceTest.php`, `tests/Feature/Admin/UserActionsTest.php`, `tests/Feature/WalletUpdateTest.php`, `tests/Feature/Api/WalletApiTest.php`, `tests/Feature/Data/DtoDataConversionTest.php`: new and extended tests.
- `docs/admin/phase-6-filament.md` (§5, §6, §8), `docs/api/phase-5-api.md`: MFA enrolment rule, export escaping, wallet clear contract.

Unchanged since pass 1: panel provider, `RequiresAdminAccess`, the four resources and widgets, admin repositories and metrics, suspension middleware and login, IP limiter, Scout subquery engine, migration, and the remaining tests and docs.

</details>
