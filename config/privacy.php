<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Privacy (LGPD / GDPR / CCPA-CPRA / POPIA)
|--------------------------------------------------------------------------
|
| consent_cookie: first-party cookie holding the visitor's cookie choices as
| JSON {"v": policy version, "a": 0|1, "m": 0|1, "t": unix time}. It is not
| encrypted (bootstrap/app.php) so resources/js/consent.js can read it.
|
| export_*: personal data exports (Configurações > Privacidade) are zip files
| on this disk, downloadable through a signed link for export_ttl_hours and
| deleted daily by `php artisan privacy:purge-exports`.
|
*/

return [
    'consent_cookie' => 'finestro_consent',
    'consent_lifetime_days' => 365,
    'dpo_email' => env('PRIVACY_DPO_EMAIL', 'contato@3wonline.com.br'),
    'export_ttl_hours' => (int) env('PRIVACY_EXPORT_TTL_HOURS', 24),
    'export_disk' => 'local',
];
