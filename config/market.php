<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Live Market Demo Settings
    |--------------------------------------------------------------------------
    |
    | These options control the behavior of the Live Market simulation.
    | When real shifts are scarce, the system can automatically inject
    | "Demo Shifts" to ensure the marketplace looks active and alive.
    |
    */

    'demo_enabled' => env('MARKET_DEMO_ENABLED', true),

    // If real open shifts are fewer than this, demo shifts will be added
    'demo_disable_threshold' => env('MARKET_DEMO_THRESHOLD', 15),

    // How many demo shifts to generate in the pool
    'demo_shift_count' => env('MARKET_DEMO_SHIFT_COUNT', 20),

    /*
    |--------------------------------------------------------------------------
    | Market Statistics Cache Settings
    |--------------------------------------------------------------------------
    */

    // Cache TTL for market statistics in seconds (default: 5 minutes)
    'stats_cache_ttl' => env('MARKET_STATS_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Worker Application Limits
    |--------------------------------------------------------------------------
    */

    // Maximum number of pending applications a worker can have at once
    'max_pending_applications' => env('MARKET_MAX_PENDING_APPLICATIONS', 5),

    /*
    |--------------------------------------------------------------------------
    | Instant Claim Settings
    |--------------------------------------------------------------------------
    */

    // Minimum worker rating required for instant claim feature
    'instant_claim_min_rating' => env('MARKET_INSTANT_CLAIM_MIN_RATING', 4.5),

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    */

    // Minimum hourly rate allowed for shifts (in dollars)
    'min_hourly_rate' => env('MARKET_MIN_HOURLY_RATE', 15),

    // Maximum surge multiplier allowed
    'max_surge_multiplier' => env('MARKET_MAX_SURGE_MULTIPLIER', 2.5),

    /*
    |--------------------------------------------------------------------------
    | Market Display Settings
    |--------------------------------------------------------------------------
    */

    // Default number of shifts to show per page
    'shifts_per_page' => env('MARKET_SHIFTS_PER_PAGE', 20),

    // Polling interval for live updates in seconds
    'polling_interval' => env('MARKET_POLLING_INTERVAL', 30),
];
