<?php

// CORS config — architecture doc 17.6: adding a new frontend (dashboard,
// future mobile web proxy, another admin panel) should only ever be a
// one-line addition here, never a code change elsewhere.

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],
    
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Dashboard uses Sanctum SPA cookie auth — cookies must be allowed.
    // Widget calls (site_key based) don't need cookies at all.
    'supports_credentials' => true,
];
