<?php

/**
 * GLO-009: Regional Pricing System Configuration
 *
 * Configuration file for regional pricing settings including PPP adjustments,
 * fee structures, and sync settings.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Country
    |--------------------------------------------------------------------------
    |
    | The default country code to use when no regional pricing is found
    | for a user's location. This should be a 2-letter ISO country code.
    |
    */
    'default_country' => env('REGIONAL_PRICING_DEFAULT_COUNTRY', 'US'),

    /*
    |--------------------------------------------------------------------------
    | World Bank API Key
    |--------------------------------------------------------------------------
    |
    | API key for accessing World Bank data for PPP (Purchasing Power Parity)
    | updates. Note: World Bank API is public but rate-limited.
    |
    */
    'ppp_api_key' => env('WORLD_BANK_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Auto Sync PPP
    |--------------------------------------------------------------------------
    |
    | Whether to automatically sync PPP rates from the World Bank API.
    | When enabled, a scheduled job will update PPP factors periodically.
    |
    */
    'auto_sync_ppp' => env('REGIONAL_PRICING_AUTO_SYNC', true),

    /*
    |--------------------------------------------------------------------------
    | Sync Interval
    |--------------------------------------------------------------------------
    |
    | Number of days between automatic PPP rate synchronizations.
    | World Bank PPP data is typically updated annually.
    |
    */
    'sync_interval_days' => env('REGIONAL_PRICING_SYNC_INTERVAL', 30),

    /*
    |--------------------------------------------------------------------------
    | Allow Region Override
    |--------------------------------------------------------------------------
    |
    | Whether to allow region-specific pricing overrides within countries.
    | When disabled, only country-level pricing will be used.
    |
    */
    'allow_region_override' => env('REGIONAL_PRICING_REGION_OVERRIDE', true),

    /*
    |--------------------------------------------------------------------------
    | Default Fee Rates
    |--------------------------------------------------------------------------
    |
    | Default fee rates used when no regional pricing is configured.
    |
    */
    'default_fees' => [
        'platform_fee_rate' => 15.00, // Percentage charged to businesses
        'worker_fee_rate' => 5.00,    // Percentage charged to workers
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Hourly Rate Limits
    |--------------------------------------------------------------------------
    |
    | Default minimum and maximum hourly rates in USD when no regional
    | pricing is configured.
    |
    */
    'default_rates' => [
        'min_hourly_rate' => 15.00,
        'max_hourly_rate' => 100.00,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tier Fee Adjustments
    |--------------------------------------------------------------------------
    |
    | Default tier-based fee adjustments. These can be overridden per region.
    | Values are multipliers (1.0 = no change, 0.9 = 10% discount).
    |
    */
    'tier_adjustments' => [
        'free' => [
            'platform_fee_modifier' => 1.0,
            'worker_fee_modifier' => 1.0,
        ],
        'basic' => [
            'platform_fee_modifier' => 0.9,  // 10% discount
            'worker_fee_modifier' => 0.95,   // 5% discount
        ],
        'professional' => [
            'platform_fee_modifier' => 0.8,  // 20% discount
            'worker_fee_modifier' => 0.9,    // 10% discount
        ],
        'enterprise' => [
            'platform_fee_modifier' => 0.7,  // 30% discount
            'worker_fee_modifier' => 0.85,   // 15% discount
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PPP Adjustment Limits
    |--------------------------------------------------------------------------
    |
    | Minimum and maximum PPP factor limits to prevent extreme adjustments.
    |
    */
    'ppp_limits' => [
        'min' => 0.10,  // Maximum 90% discount
        'max' => 3.00,  // Maximum 200% markup
    ],

    /*
    |--------------------------------------------------------------------------
    | Surge Pricing Settings
    |--------------------------------------------------------------------------
    |
    | Settings for surge pricing adjustments during high-demand periods.
    |
    */
    'surge' => [
        'enabled' => env('REGIONAL_PRICING_SURGE_ENABLED', true),
        'max_multiplier' => 2.5,  // Maximum surge multiplier
        'weekend_default' => 1.15, // Default weekend multiplier
        'holiday_default' => 1.25, // Default holiday multiplier
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache configuration for regional pricing lookups.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,  // Cache TTL in seconds (1 hour)
        'prefix' => 'regional_pricing_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Regions
    |--------------------------------------------------------------------------
    |
    | List of explicitly supported regions. If empty, all regions in the
    | database are considered supported.
    |
    */
    'supported_regions' => [
        // North America
        'US', 'CA', 'MX',
        // Europe
        'GB', 'DE', 'FR', 'NL', 'ES', 'IT', 'IE', 'PL',
        // Oceania
        'AU', 'NZ',
        // Asia
        'IN', 'PH', 'SG', 'JP', 'KR',
        // Middle East
        'AE', 'SA',
        // Africa
        'NG', 'ZA', 'KE', 'GH', 'EG',
        // South America
        'BR', 'AR', 'CL', 'CO',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Validation
    |--------------------------------------------------------------------------
    |
    | Settings for validating rates against regional limits.
    |
    */
    'validation' => [
        'strict_mode' => env('REGIONAL_PRICING_STRICT_VALIDATION', false),
        'allow_override' => env('REGIONAL_PRICING_ALLOW_OVERRIDE', true),
        'log_violations' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Settings
    |--------------------------------------------------------------------------
    |
    | Settings for regional pricing analytics and reporting.
    |
    */
    'analytics' => [
        'enabled' => true,
        'track_adjustments' => true,
        'retention_days' => 90,
    ],
];
