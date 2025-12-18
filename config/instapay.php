<?php

/**
 * InstaPay Configuration
 *
 * FIN-004: InstaPay (Same-Day Payout) Feature
 *
 * Configuration settings for the InstaPay instant/same-day payout system.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | InstaPay Enabled
    |--------------------------------------------------------------------------
    |
    | This option enables or disables the InstaPay feature globally.
    | When disabled, no instant payouts can be requested.
    |
    */
    'enabled' => env('INSTAPAY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Fee Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the fees charged for InstaPay requests.
    | The fee is calculated as a percentage of the payout amount,
    | with minimum and maximum bounds.
    |
    | fee_percent: Percentage fee (1.5 = 1.5%)
    | fee_minimum: Minimum fee charged (in currency units)
    | fee_maximum: Maximum fee charged (in currency units)
    |
    */
    'fee_percent' => env('INSTAPAY_FEE_PERCENT', 1.5),
    'fee_minimum' => env('INSTAPAY_FEE_MINIMUM', 0.50),
    'fee_maximum' => env('INSTAPAY_FEE_MAXIMUM', 10.00),

    /*
    |--------------------------------------------------------------------------
    | Amount Limits
    |--------------------------------------------------------------------------
    |
    | Configure the minimum and maximum amounts for InstaPay requests.
    |
    | daily_limit: Maximum amount that can be requested per day
    | minimum_amount: Minimum amount required for a request
    | maximum_single_request: Maximum amount for a single request
    |
    */
    'daily_limit' => env('INSTAPAY_DAILY_LIMIT', 500.00),
    'minimum_amount' => env('INSTAPAY_MINIMUM_AMOUNT', 10.00),
    'maximum_single_request' => env('INSTAPAY_MAX_SINGLE_REQUEST', 500.00),

    /*
    |--------------------------------------------------------------------------
    | Cutoff Time
    |--------------------------------------------------------------------------
    |
    | The daily cutoff time for same-day processing.
    | Requests made after this time will be processed the next business day.
    |
    | cutoff_time: Time in 24-hour format (HH:MM)
    | cutoff_timezone: Timezone for the cutoff time
    |
    */
    'cutoff_time' => env('INSTAPAY_CUTOFF_TIME', '14:00'),
    'cutoff_timezone' => env('INSTAPAY_CUTOFF_TIMEZONE', 'Europe/Malta'),

    /*
    |--------------------------------------------------------------------------
    | Processing Days
    |--------------------------------------------------------------------------
    |
    | Days of the week when InstaPay requests are processed.
    | Requests made outside these days will be queued for the next processing day.
    |
    */
    'processing_days' => [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for InstaPay transactions.
    |
    */
    'default_currency' => env('INSTAPAY_DEFAULT_CURRENCY', 'EUR'),

    /*
    |--------------------------------------------------------------------------
    | Payout Methods
    |--------------------------------------------------------------------------
    |
    | Available payout methods and their configuration.
    |
    */
    'payout_methods' => [
        'stripe' => [
            'enabled' => env('INSTAPAY_STRIPE_ENABLED', true),
            'instant' => true, // Stripe supports instant payouts
            'fee_percent' => 1.5, // Additional fee for instant
        ],
        'paypal' => [
            'enabled' => env('INSTAPAY_PAYPAL_ENABLED', false),
            'instant' => false,
            'fee_percent' => 0,
        ],
        'bank_transfer' => [
            'enabled' => env('INSTAPAY_BANK_ENABLED', true),
            'instant' => false,
            'fee_percent' => 0,
            'processing_days' => 1, // 1-2 business days
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Eligibility Requirements
    |--------------------------------------------------------------------------
    |
    | Requirements for a user to be eligible for InstaPay.
    |
    */
    'eligibility' => [
        'min_completed_shifts' => env('INSTAPAY_MIN_SHIFTS', 3),
        'min_reliability_score' => env('INSTAPAY_MIN_RELIABILITY', 70),
        'require_verified' => env('INSTAPAY_REQUIRE_VERIFIED', true),
        'require_payment_method' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Request Settings
    |--------------------------------------------------------------------------
    |
    | Settings for automatic InstaPay requests after shift completion.
    |
    */
    'auto_request' => [
        'enabled' => env('INSTAPAY_AUTO_REQUEST_ENABLED', true),
        'delay_minutes' => 15, // Delay after shift completion before auto-requesting
        'min_amount' => 20.00, // Minimum earnings to trigger auto-request
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Processing
    |--------------------------------------------------------------------------
    |
    | Settings for batch processing of pending requests.
    |
    */
    'batch' => [
        'enabled' => env('INSTAPAY_BATCH_ENABLED', true),
        'size' => 50, // Process up to 50 requests per batch
        'interval_minutes' => 5, // Process batch every 5 minutes
        'max_retries' => 3, // Maximum retry attempts for failed requests
        'retry_delay_minutes' => 30, // Delay before retrying failed requests
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Notification settings for InstaPay events.
    |
    */
    'notifications' => [
        'request_submitted' => true,
        'request_processing' => true,
        'request_completed' => true,
        'request_failed' => true,
        'channels' => ['mail', 'database'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fraud Prevention
    |--------------------------------------------------------------------------
    |
    | Settings to prevent fraudulent InstaPay requests.
    |
    */
    'fraud_prevention' => [
        'max_daily_requests' => 5, // Maximum requests per user per day
        'cooldown_minutes' => 30, // Minimum time between requests
        'require_completed_shift' => true, // Require earnings from completed shifts
        'flag_large_requests' => true, // Flag requests above threshold for review
        'large_request_threshold' => 300.00,
    ],
];
