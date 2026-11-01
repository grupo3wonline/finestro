# Phase 7: Privacy compliance (LGPD, GDPR, CCPA/CPRA, POPIA)

This document maps what each privacy law asks of Finestro to the code that
implements it, describes the consent design and lists what the operator still
has to fill in. It describes the code as of phase 7; it is not legal advice.
Have the policy texts reviewed by a lawyer before launch.

## 1. Requirements per law

| Requirement | LGPD (BR) | GDPR (EU) | CCPA/CPRA (US-CA) | POPIA (ZA) | Where it is implemented |
| --- | --- | --- | --- | --- | --- |
| Identify the controller and the DPO / Information Officer | Art. 9, 41 | Art. 13 | §1798.100 | s. 18, 55 | `pages/privacy.blade.php` `#controlador`, `#encarregado`; values from `config/legal.php` (`controller.*`) and `config/privacy.php` (`dpo_email`) |
| Purposes and legal bases | Art. 7, 9 | Art. 6, 13 | §1798.100 | s. 11, 13 | `pages/privacy.blade.php` `#dados-coletados`, `#finalidades` |
| Sharing and international transfers | Art. 33 | Art. 44-49 | §1798.115 | s. 72 | `#compartilhamento`, `#transferencias` (Stripe, hosting, optional Google/Meta) |
| Retention periods | Art. 15, 16 | Art. 5(1)(e) | §1798.100(a)(3) | s. 14 | `#retencao`; see section 5 below |
| Access and portability | Art. 18 II, V | Art. 15, 20 | §1798.110 | s. 23 | Settings > Privacidade: data export (zip with JSON + CSV), section 4 |
| Correction | Art. 18 III | Art. 16 | §1798.106 | s. 24 | `profile.edit`, wallet/transaction/goal edit screens |
| Deletion | Art. 18 VI | Art. 17 | §1798.105 | s. 24 | `settings.account.delete` (`AccountDeletionService`), section 4 |
| Consent before non-essential cookies, withdraw any time | Art. 8 §5 | Art. 7, ePrivacy 5(3) | n/a (opt-out model) | s. 11(2) | Cookie banner + `POST /consentimento`, section 2 |
| Opt-out of sale/sharing, honour Global Privacy Control | n/a | n/a | §1798.120, §1798.135, 11 CCR §7025 | n/a | `/cookies#nao-vender`, footer link "Não venda ou compartilhe minhas informações", `Sec-GPC` handling, section 2 |
| Proof of consent | Art. 8 §2 | Art. 7(1) | n/a | s. 11 | `cookie_consents` table, `terms_accepted_at` + `terms_version`, activity events |
| Children | Art. 14 | Art. 8 | §1798.120(c) | s. 34 | `#criancas` (service is for adults) |
| Security measures | Art. 46 | Art. 32 | §1798.150 | s. 19 | `#seguranca`; `SecurityHeaders`, CSRF, honeypot, rate limits, per-user query scoping, MFA on `/admin` |
| Policy changes are announced and re-accepted | Art. 8 §6 | Art. 13(3) | §1798.130 | s. 18 | `config('legal.policy_version')` gate, section 3 |

The privacy page sections are anchored as `#controlador`, `#encarregado`,
`#dados-coletados`, `#finalidades`, `#compartilhamento`, `#transferencias`,
`#retencao`, `#seus-direitos`, `#como-exercer`, `#ccpa`, `#criancas`,
`#seguranca`, `#cookies` and `#alteracoes`.

## 2. Cookie consent design

- **Cookie**: first-party `finestro_consent`, JSON
  `{"v": policy_version, "a": 0|1, "m": 0|1, "g": 0|1, "t": unix_time}`
  (`a` analytics, `m` marketing, `g` GPC was active). 365 days
  (`config('privacy.consent_lifetime_days')`), `SameSite=Lax`, not `HttpOnly`
  (the browser script reads it), excluded from encryption in `bootstrap/app.php`
  (`encryptCookies(except: ['finestro_consent'])`).
- **Versioning**: a cookie whose `v` differs from `config('legal.policy_version')`,
  a malformed cookie or one without `t` counts as "no decision". The banner shows
  again and nothing non-essential loads.
- **Single write path**: `POST /consentimento` (`consent.store`,
  `ConsentController@store`, `StoreConsentRequest`, throttle 30/min). It accepts
  JSON (banner) and regular forms (Settings > Privacidade, `/cookies`).
  `ConsentService::record()` queues the cookie; for signed-in users it also writes
  a `cookie_consents` row (version, analytics, marketing, gpc, source,
  timestamps) and logs `consent_updated` on the `security` log with
  `{version, analytics, marketing}` plus the IP the logger always adds.
  Guests get the cookie only, no database row.
- **Banner** (`components/layout/cookie-consent.blade.php`, Alpine data
  `cookieConsent` from `resources/js/consent.js`): "Aceitar todos",
  "Rejeitar não essenciais" with equal visual weight, and "Personalizar" with
  per-category toggles. The footer button "Preferências de cookies" reopens it
  (`open-cookie-preferences` event).
- **Global Privacy Control**: the `Sec-GPC: 1` request header or
  `navigator.globalPrivacyControl` forces `marketing = false`; the stored row and
  cookie record `gpc = true`. The banner explains it.
- **Tag loading**: `components/layout/analytics.blade.php` prints only a JSON
  config block (`#finestro-consent-config`); no vendor URL is in the HTML before
  consent. `resources/js/consent.js` pushes Google **Consent Mode v2** defaults
  (`ad_storage`, `ad_user_data`, `ad_personalization`, `analytics_storage` all
  `denied`), loads GTM/GA4 only with analytics consent and then sends
  `consent update`; the Meta Pixel loads only with marketing consent.
  `components/layout/gtm-noscript.blade.php` renders the GTM `<noscript>` iframe
  only when the server-side cookie grants analytics.
- **CSP additions** (`app/Http/Middleware/SecurityHeaders.php`): `script-src` and
  `connect-src` add `https://*.google-analytics.com` and
  `https://*.analytics.google.com`; `connect-src` adds `https://www.facebook.com`.
  Nothing else was widened.
- **Cookie list**: `/cookies` (`pages/cookies.blade.php`) lists the session cookie
  (`config('session.cookie')`), `XSRF-TOKEN`, `remember_web_*`, `finestro_consent`,
  the `darkMode` localStorage key, and `_ga`, `_ga_*`, `_fbp` (only when the IDs
  are configured and the visitor consented).

## 3. Terms and policy versioning

- `config/legal.php`: `policy_version` (env `LEGAL_POLICY_VERSION`, default
  `2026-07`) and `policy_updated_at`.
- `users.terms_version` (migration `2026_12_01_000100_add_terms_version_to_users_table`).
  Registration stores the current version.
- `EnsureTermsAccepted` asks `LegalConsentService::needsAcceptance()`; when the
  version changed the user is sent to `/aceitar-termos`, which shows a
  "nova versão" notice. Acceptance (`TermsAcceptanceController@store`) stores
  `terms_accepted_at` + `terms_version` and logs `terms_accepted`.
- Data subject rights never depend on accepting a new version (LGPD art. 18,
  GDPR arts. 15/17/20): `EnsureTermsAccepted::$except` also lets through
  `settings.privacy`, `settings.privacy.export`, `settings.privacy.download`
  and `settings.account.delete` (logout lives outside the gated group). Their
  own safeguards stay: `auth` + `verified`, password confirmation and the
  deletion warning, signed expiring download links, owner-only exports and the
  export throttle. `/aceitar-termos` links to them with "Prefiro exportar ou
  excluir meus dados". Every other gated route still redirects to
  `/aceitar-termos` (`PolicyVersionGateTest`).
- To publish a new policy: edit the texts, bump `LEGAL_POLICY_VERSION` and
  `LEGAL_POLICY_UPDATED_AT`. Every user re-accepts once and every visitor sees
  the cookie banner again.

## 4. Data subject requests

| Request | How | Code |
| --- | --- | --- |
| Export (access/portability) | Settings > Privacidade > "Solicitar exportação" (throttle 3/hour). A queued job builds `exports/{user_id}/{export_id}.zip` on the `local` disk with `data.json` and one CSV per section (account, wallets, transactions, categories, goals, budgets, subscriptions, payments, receipts metadata, cookie consents, activity). CSV cells go through `EscapesFormulaInjection`. | `PrivacySettingsController@export`, `DataExportService`, `GeneratePersonalDataExport`, `PersonalDataRepository` |
| Download | Temporary signed URL valid for `PRIVACY_EXPORT_TTL_HOURS` (24 h). Other users get 404, expired exports 410, unsigned links 403. | `settings.privacy.download` (`signed`) |
| Deletion | Settings > Privacidade > "Excluir conta" with password and SweetAlert2 confirmation. Cancels the Stripe subscription, revokes API tokens, soft-deletes the account, logs `account_deleted`, ends the session. | `SettingController@deleteAccount`, `AccountDeletionService` |
| History | Consent history and terms acceptance in Settings > Privacidade; account activity in Settings > Atividade. | `settings.privacy`, `settings.activity` |
| Opt-out of sale/sharing | `/cookies#nao-vender` button, footer link, GPC. | `consent.store` |
| Anything else (correction help, permanent erasure, objection) | E-mail to the DPO address (`config('privacy.dpo_email')`). | `#como-exercer` |

Activity entries for exports carry only `export_id` and `ip`; consent entries the
version, the two choices and `ip`. No names, e-mails or financial values are logged.

## 5. Retention

| Data | Period | Mechanism |
| --- | --- | --- |
| Account and financial records | While the account exists; soft-deleted on deletion | `SoftDeletes`; hard purge is deferred (section 7) |
| Activity log | `ACTIVITYLOG_RETENTION_DAYS` (default 365) | `activitylog:clean --force` scheduled in `routes/console.php` |
| Data export files | `PRIVACY_EXPORT_TTL_HOURS` (default 24 h) | `privacy:purge-exports`, scheduled daily |
| Consent cookie | 365 days or until the policy version changes | cookie expiry + version check |
| Expired API tokens | per token expiry | `sanctum:prune-expired` scheduled |
| Remember-me cookie | up to 400 days (Laravel default) | framework |

## 6. Placeholders the operator must fill

Set these in `.env` (they are commented in `.env.example` because an empty value
would override the visible `[PREENCHER: ...]` default):

- `LEGAL_CONTROLLER_NAME`: legal name of the data controller.
- `LEGAL_CONTROLLER_CNPJ`: CNPJ.
- `LEGAL_CONTROLLER_ADDRESS`: full address.
- `PRIVACY_DPO_EMAIL`: DPO / Encarregado e-mail (default `contato@3wonline.com.br`).
- `GOOGLE_TAG_MANAGER_ID`, `GOOGLE_ANALYTICS_ID`, `FACEBOOK_PIXEL_ID`: only if
  analytics/marketing tags are wanted; empty means no tag is ever loaded.
- `LEGAL_POLICY_VERSION`, `LEGAL_POLICY_UPDATED_AT`: bump on every policy change.
- `MARKETING_CTA_MONTHLY_SLOTS`: the real number of new Grupo 3W Online projects
  accepted per month, shown in the agency CTA as "Atendemos no máximo N novos
  projetos por mês". The business must confirm this figure before go-live
  (truthful-copy rule, Brazilian CDC). There is no default: unless it is a
  positive integer the line is hidden and the CTA shows its other two bullets.

Until the controller values are set, the privacy and terms pages show the
`[PREENCHER: ...]` markers on purpose.

Release checklist: `LEGAL_CONTROLLER_NAME`, `LEGAL_CONTROLLER_CNPJ`,
`LEGAL_CONTROLLER_ADDRESS` and `PRIVACY_DPO_EMAIL` must be set in the
production `.env`; no page may show `[PREENCHER: ...]` in production.

## 7. Deferred

- **Automated hard purge of soft-deleted accounts.** Deleted accounts are
  deactivated (soft delete) and access ends immediately; the UI and policy say
  so and offer permanent erasure on request to the DPO e-mail. A scheduled purge
  after a grace period (with the cascade over media files) is not implemented.
- Queue worker in production: exports stay "Em preparação" until `queue:work`
  (or the scheduler-driven queue) runs. A lost job cannot block the user:
  `GeneratePersonalDataExport::failed()` marks the export Failed, a Pending
  export older than `DataExport::STALE_AFTER_MINUTES` (60) shows "Falhou" and
  no longer blocks a new request, and the daily `privacy:purge-exports` marks
  such rows Failed.

## 8. Tests

`tests/Feature/Privacy/ConsentTest.php`, `DataExportTest.php`,
`PrivacyPagesTest.php`, `tests/Feature/Auth/PolicyVersionGateTest.php`,
`tests/Feature/AccountDeletionTest.php` and the journeys in
`tests/Feature/Flows/UserJourneyTest.php` (consent before analytics; export then
deletion). See [phase-7-tests.md](../testing/phase-7-tests.md).
