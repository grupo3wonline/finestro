<?php

declare(strict_types=1);

return [
    // Requests per minute per user (shared by all of the user's tokens).
    'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),

    // Requests per minute per client IP, checked before token authentication
    // (caps unauthenticated and invalid-token traffic).
    'ip_rate_limit_per_minute' => (int) env('API_IP_RATE_LIMIT_PER_MINUTE', 120),

    // Upper bound for the `per_page` query parameter on list endpoints.
    'per_page_max' => 100,

    // Swagger UI is open in the local environment; elsewhere admins only.
    'docs_public_in_local' => true,
];
