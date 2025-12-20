<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | For production, we restrict CORS to specific domains.
    | In development/local, we allow all origins.
    |
    */
    'allowed_origins' => env('APP_ENV') === 'production'
        ? [
            'https://www.overtimestaff.com',
            'https://overtimestaff.com',
        ]
        : ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origin Patterns
    |--------------------------------------------------------------------------
    |
    | Regex patterns for matching allowed origins (e.g., agency subdomains).
    |
    */
    'allowed_origins_patterns' => [
        // Allow all agency subdomains (white-label)
        '#^https://[a-z0-9-]+\.overtimestaff\.com$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Set to true to allow cookies/authentication headers in CORS requests.
    | Required for Sanctum SPA authentication.
    |
    */
    'supports_credentials' => true,

];
