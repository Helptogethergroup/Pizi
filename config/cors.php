<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_filter(array_merge(
        [
            'http://localhost:3000',
            'http://localhost:3001',
            'https://pizi.in',
            'https://www.pizi.in',
        ],
        // Extra browser origins for local development of the owner app
        // (e.g. http://localhost:8081). Leave unset in production.
        array_map('trim', explode(',', (string) env('CORS_EXTRA_ORIGINS', '')))
    ))),
    'allowed_origins_patterns' => [
        '#^https://.*\.vercel\.app$#',
        '#^https://.*\.netlify\.app$#',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
