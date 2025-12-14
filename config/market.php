<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Demo Mode Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, the market will display demo shifts if real shifts are
    | below the threshold. Demo shifts are auto-generated and marked as such.
    |
    */

    'demo_enabled' => env('MARKET_DEMO_ENABLED', true),

    'demo_disable_threshold' => env('MARKET_DEMO_THRESHOLD', 10),

    'demo_shift_count' => env('MARKET_DEMO_COUNT', 15),

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    |
    | Configure minimum and maximum values for shift rates and surge pricing.
    |
    */

    'min_hourly_rate' => env('MARKET_MIN_RATE', 12.00),

    'max_surge_multiplier' => env('MARKET_MAX_SURGE', 3.00),

    /*
    |--------------------------------------------------------------------------
    | Instant Claim Configuration
    |--------------------------------------------------------------------------
    |
    | Workers with ratings above this threshold can instant-claim shifts
    | without going through the application process.
    |
    */

    'instant_claim_min_rating' => env('MARKET_INSTANT_CLAIM_RATING', 4.5),

    /*
    |--------------------------------------------------------------------------
    | Application Limits
    |--------------------------------------------------------------------------
    |
    | Maximum number of pending applications a worker can have simultaneously.
    |
    */

    'max_pending_applications' => env('MARKET_MAX_PENDING_APPS', 5),

    /*
    |--------------------------------------------------------------------------
    | Statistics Cache
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) to cache market statistics for performance.
    |
    */

    'stats_cache_ttl' => env('MARKET_STATS_CACHE_TTL', 300), // 5 minutes

    /*
    |--------------------------------------------------------------------------
    | Real-time Updates
    |--------------------------------------------------------------------------
    |
    | Polling interval for live market updates (in milliseconds).
    |
    */

    'polling_interval' => env('MARKET_POLLING_INTERVAL', 30000), // 30 seconds

    /*
    |--------------------------------------------------------------------------
    | Featured Shifts
    |--------------------------------------------------------------------------
    |
    | Configuration for featured/promoted shifts in the market.
    |
    */

    'featured_enabled' => env('MARKET_FEATURED_ENABLED', false),

    'featured_boost_multiplier' => env('MARKET_FEATURED_BOOST', 1.2),
];
