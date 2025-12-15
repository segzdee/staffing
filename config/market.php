<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Live Market Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the OvertimeStaff Live Shift Market.
    | This includes demo shift settings, caching, and business rules.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Demo Shifts
    |--------------------------------------------------------------------------
    */

    'demo_enabled' => env('MARKET_DEMO_ENABLED', true),
    'demo_disable_threshold' => env('MARKET_DEMO_THRESHOLD', 10),
    'demo_shift_count' => env('MARKET_DEMO_COUNT', 15),

    'demo_statistics' => [
        'shifts_live' => 247,
        'total_value' => 42500,
        'avg_hourly_rate' => 32,
        'rate_change_percent' => 3.2,
        'filled_today' => 89,
        'workers_online' => 1247,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */

    'stats_cache_ttl' => env('MARKET_STATS_CACHE_TTL', 300),
    'shifts_cache_ttl' => env('MARKET_SHIFTS_CACHE_TTL', 30),

    /*
    |--------------------------------------------------------------------------
    | Worker Applications
    |--------------------------------------------------------------------------
    */

    'max_pending_applications' => env('MARKET_MAX_PENDING_APPS', 5),
    'max_confirmed_shifts' => env('MARKET_MAX_CONFIRMED_SHIFTS', 3),
    'application_cutoff_hours' => env('MARKET_APP_CUTOFF_HOURS', 2),

    /*
    |--------------------------------------------------------------------------
    | Instant Claim
    |--------------------------------------------------------------------------
    */

    'instant_claim_min_rating' => env('MARKET_INSTANT_CLAIM_MIN_RATING', 4.5),
    'instant_claim_min_shifts_completed' => env('MARKET_INSTANT_CLAIM_MIN_SHIFTS', 5),
    'instant_claim_enabled' => env('MARKET_INSTANT_CLAIM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Surge Pricing
    |--------------------------------------------------------------------------
    */

    'surge_urgent_hours' => env('MARKET_SURGE_URGENT_HOURS', 12),
    'max_surge_multiplier' => env('MARKET_MAX_SURGE', 2.5),
    'weekend_surge_multiplier' => env('MARKET_WEEKEND_SURGE', 1.15),
    'night_surge_multiplier' => env('MARKET_NIGHT_SURGE', 1.20),
    'holiday_surge_multiplier' => env('MARKET_HOLIDAY_SURGE', 1.50),

    /*
    |--------------------------------------------------------------------------
    | Matching Algorithm
    |--------------------------------------------------------------------------
    */

    'matching_enabled' => env('MARKET_MATCHING_ENABLED', true),
    'matching_weights' => [
        'skills' => 0.30,
        'distance' => 0.25,
        'availability' => 0.20,
        'rating' => 0.15,
        'reliability' => 0.10,
    ],
    'max_match_distance' => env('MARKET_MAX_MATCH_DISTANCE', 50),

    /*
    |--------------------------------------------------------------------------
    | Market Display
    |--------------------------------------------------------------------------
    */

    'shifts_per_page' => env('MARKET_SHIFTS_PER_PAGE', 20),
    'landing_page_limit' => env('MARKET_LANDING_LIMIT', 6),
    'poll_interval' => env('MARKET_POLL_INTERVAL', 30000),
    'activity_poll_interval' => env('MARKET_ACTIVITY_POLL_INTERVAL', 5000),

    /*
    |--------------------------------------------------------------------------
    | Industries & Role Types
    |--------------------------------------------------------------------------
    */

    'industries' => [
        'hospitality', 'healthcare', 'retail', 'logistics',
        'construction', 'events', 'manufacturing', 'food_service',
        'security', 'cleaning',
    ],

    'role_types' => [
        'server', 'bartender', 'nurse', 'cashier', 'warehouse',
        'laborer', 'event_staff', 'line_cook', 'security_guard',
        'cleaner', 'driver', 'general',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    'notify_new_matches' => env('MARKET_NOTIFY_MATCHES', true),
    'notify_upcoming_hours' => env('MARKET_NOTIFY_UPCOMING', 24),
    'notify_filling_threshold' => env('MARKET_NOTIFY_FILLING', 2),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit_authenticated' => env('MARKET_RATE_LIMIT_AUTH', 60),
    'rate_limit_guest' => env('MARKET_RATE_LIMIT_GUEST', 30),

    /*
    |--------------------------------------------------------------------------
    | Analytics & Tracking
    |--------------------------------------------------------------------------
    */

    'track_views' => env('MARKET_TRACK_VIEWS', true),
    'track_applications' => env('MARKET_TRACK_APPLICATIONS', true),
    'track_conversion' => env('MARKET_TRACK_CONVERSION', true),

];
