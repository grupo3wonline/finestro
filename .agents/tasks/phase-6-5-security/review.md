# Phase 6.5 review (pass 1)

Verdict: APPROVED. No blockers.

## Evidence checked

- Dependencies: `composer.json` differs from `backup/composer.json` only in `laravel/framework` `^13.8` -> `^13.39`. A lock comparison against `backup/composer.lock` shows 43 changed packages. Every change is a patch or minor on the same major line, and nothing was removed. The one addition is `symfony/polyfill-time` v1.42.0, a new dependency of Symfony 8.1. Guzzle is on 7.15.6, psr7 2.13.2, promises 2.6.0, uri-template 1.0.11, commonmark 2.10.7, flysystem 3.36.0 and Symfony 8.1.10. symfony/console stays 7.4 and brick/math stays 0.14.8, so none of the unwanted major bumps happened.
- Audit: `audit-after.json` has `"advisories": []`. The only entry under abandoned is the dev-only `symplify/rule-doc-generator-contracts`, which is documented.
- XLSX fix: `EscapesFormulaInjection::text()` adds the apostrophe only when `writerType() === Excel::CSV`, and CSV is the constructor default. Both exports extend `StringValueBinder`. In the installed PhpSpreadsheet, `$convertFormula = true` makes `bindValue()` fall through to `setValueExplicit((string) $value, TYPE_STRING)`, so a value starting with `=` is stored as a string and never as a formula. Every caller passes the correct writer type:
  - `ReportService::exportCsv` passes CSV, and `FinancialReportMail` uses that path, so it stays guarded.
  - `ReportService::exportXlsx` passes XLSX.
  - `ListPaymentHistories` passes the writer type to both the export and `Excel::download`.
- Tests: the new tests in `ReportExportTest` and `BillingResourcesTest` check that the XLSX cell value equals the original text, that the type is `TYPE_STRING`, that `isFormula()` is false, and that the sheet XML has no `<f>`. They also check that the CSV keeps the guard and that each header action builds the export for its own writer.
- CI: `quality.yml` now runs `composer audit --locked --no-interaction --abandoned=report` after `composer install` and `npm audit --omit=dev --audit-level=high` after `npm ci`.
- Verification logs:
  - `quality-1.log`: 860 passed, 15 skipped, 3740 assertions.
  - `test-random-1.log`: the same counts, seed 1793500315.
  - `npm-build-1.log`: the build succeeded.
  - The run log records exit 0 for route:list, l5-swagger:generate, view:cache/clear, filament:optimize/clear, both npm audits, and the admin asset HTTP checks.
  - `phpstan-baseline.neon` still has 3 entries.
- Docs: `docs/security/phase-6-5-dependency-update.md` covers every item the brief requires: the before/after table, advisories fixed, no major bumps, no remaining advisories, the CI steps and the revert steps.

## Findings

None.
