<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Legal documents
|--------------------------------------------------------------------------
|
| policy_version identifies the current Terms of Use + Privacy Policy.
| Changing it makes every user accept the documents again (see
| App\Services\LegalConsentService and the terms.accepted middleware).
|
*/

return [
    'policy_version' => env('LEGAL_POLICY_VERSION', '2026-07'),

    'policy_updated_at' => env('LEGAL_POLICY_UPDATED_AT', '2026-07-01'),

    'controller' => [
        'legal_name' => env('LEGAL_CONTROLLER_NAME', '[PREENCHER: Razão social]'),
        'cnpj' => env('LEGAL_CONTROLLER_CNPJ', '[PREENCHER: CNPJ]'),
        'address' => env('LEGAL_CONTROLLER_ADDRESS', '[PREENCHER: Endereço completo]'),
    ],
];
