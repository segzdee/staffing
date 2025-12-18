<?php

/**
 * WKR-006: Earnings Configuration
 *
 * Configuration for the worker earnings dashboard and management system.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency code for earnings when not specified.
    | Uses ISO 4217 currency codes (e.g., USD, EUR, GBP).
    |
    */
    'default_currency' => env('EARNINGS_DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Tax Withholding
    |--------------------------------------------------------------------------
    |
    | Enable or disable automatic tax withholding calculations.
    | When enabled, taxes will be calculated based on worker's jurisdiction.
    |
    */
    'tax_withholding_enabled' => env('EARNINGS_TAX_WITHHOLDING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Tax Rate
    |--------------------------------------------------------------------------
    |
    | Default tax withholding rate when worker's jurisdiction rate is not available.
    | Value is a decimal (0.15 = 15%).
    |
    */
    'default_tax_rate' => env('EARNINGS_DEFAULT_TAX_RATE', 0),

    /*
    |--------------------------------------------------------------------------
    | Platform Fee Rate
    |--------------------------------------------------------------------------
    |
    | The platform fee rate applied to gross earnings.
    | Value is a decimal (0.10 = 10%).
    |
    */
    'platform_fee_rate' => env('EARNINGS_PLATFORM_FEE_RATE', 0.10),

    /*
    |--------------------------------------------------------------------------
    | Export Formats
    |--------------------------------------------------------------------------
    |
    | Available export formats for earnings data.
    |
    */
    'export_formats' => ['csv', 'pdf'],

    /*
    |--------------------------------------------------------------------------
    | Summary Retention
    |--------------------------------------------------------------------------
    |
    | Number of years to retain earnings summary data.
    | Summaries older than this will be eligible for cleanup.
    |
    */
    'summary_retention_years' => env('EARNINGS_SUMMARY_RETENTION_YEARS', 7),

    /*
    |--------------------------------------------------------------------------
    | 1099 Threshold
    |--------------------------------------------------------------------------
    |
    | IRS threshold for 1099 reporting requirement (in USD).
    | Workers earning more than this in a year require 1099 forms.
    |
    */
    '1099_threshold' => env('EARNINGS_1099_THRESHOLD', 600),

    /*
    |--------------------------------------------------------------------------
    | Summary Refresh Schedule
    |--------------------------------------------------------------------------
    |
    | When to automatically refresh earnings summaries.
    | Supported values: daily, weekly, manual
    |
    */
    'summary_refresh_schedule' => env('EARNINGS_SUMMARY_REFRESH', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Chart Months
    |--------------------------------------------------------------------------
    |
    | Number of months to display in the dashboard earnings chart.
    |
    */
    'dashboard_chart_months' => env('EARNINGS_DASHBOARD_CHART_MONTHS', 6),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for earnings lists.
    |
    */
    'pagination' => [
        'history' => 20,
        'dashboard_recent' => 10,
        'export_limit' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Cache settings for earnings data.
    |
    */
    'cache' => [
        'enabled' => env('EARNINGS_CACHE_ENABLED', true),
        'ttl' => env('EARNINGS_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'earnings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Triggers
    |--------------------------------------------------------------------------
    |
    | Events that trigger earnings-related notifications.
    |
    */
    'notifications' => [
        'on_payment_received' => true,
        'on_1099_threshold_reached' => true,
        'weekly_summary_enabled' => true,
        'weekly_summary_day' => 'monday', // Day to send weekly summary
    ],
];
