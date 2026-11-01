<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Only the REST API is exposed cross-origin, and only to the origins listed
    | in CORS_ALLOWED_ORIGINS (comma separated; defaults to APP_URL). The API is
    | token-only, so credentials (cookies) are never allowed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        trim(...),
        // An empty CORS_ALLOWED_ORIGINS falls back to APP_URL as well.
        explode(',', (string) (env('CORS_ALLOWED_ORIGINS') ?: env('APP_URL', '')))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Authorization', 'Content-Type', 'Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 600,

    'supports_credentials' => false,

];
