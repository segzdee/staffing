<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Financial Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for platform fees, taxes, and buffers.
    |
    */
    'financial' => [
        'platform_fee_rate' => 35.00, // Percentage
        'vat_rate' => 18.00, // Percentage (Malta)
        'contingency_buffer_rate' => 5.00, // Percentage
        'currency' => 'EUR',
    ],

    /*
    |--------------------------------------------------------------------------
    | Surge Pricing Settings
    |--------------------------------------------------------------------------
    |
    | SL-008: Comprehensive surge pricing configuration.
    | Includes time-based, demand-based, and event-based surge multipliers.
    |
    */
    'surge' => [
        // Legacy simple multipliers (kept for backward compatibility)
        'urgent_shift' => 0.50, // +50%
        'night_shift' => 0.30, // +30%
        'weekend' => 0.20, // +20%
        'public_holiday' => 0.50, // +50%

        // Time-based surge configuration
        'time_based' => [
            'enabled' => true,
            'night_hours' => ['22:00', '06:00'], // Night shift window
            'night_multiplier' => 1.25, // 1.25x for night shifts
            'weekend_multiplier' => 1.15, // 1.15x for weekend shifts
            'urgent_multiplier' => 1.50, // 1.50x for urgent (<24h) shifts
        ],

        // Demand-based surge configuration
        'demand_based' => [
            'enabled' => true,
            // Thresholds based on fill rate (percentage of shifts filled)
            // Higher fill rate = lower surge (less demand pressure)
            'thresholds' => [
                ['ratio' => 0.8, 'multiplier' => 1.0],  // 80%+ fill rate = no surge
                ['ratio' => 0.6, 'multiplier' => 1.2],  // 60-80% fill rate = 1.2x
                ['ratio' => 0.4, 'multiplier' => 1.5],  // 40-60% fill rate = 1.5x
                ['ratio' => 0.0, 'multiplier' => 2.0],  // <40% fill rate = 2.0x (critical)
            ],
        ],

        // Event-based surge configuration
        'event_based' => [
            'enabled' => true,
            'max_multiplier' => 2.5, // Cap event surge at 2.5x
        ],

        // How to combine multiple surge types
        // 'highest' = use the highest single multiplier
        // 'multiplicative' = multiply all surge factors together
        'combination_method' => 'highest',

        // Maximum total surge multiplier allowed (safety cap)
        'cap' => 3.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Operational Settings
    |--------------------------------------------------------------------------
    |
    | Default values for shift operations.
    |
    */
    'operations' => [
        'default_geofence_radius' => 100, // meters
        'early_clockin_minutes' => 15,
        'late_grace_minutes' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Holidays (Malta)
    |--------------------------------------------------------------------------
    |
    | List of public holidays for surge pricing calculation.
    | Format: YYYY-MM-DD
    |
    */
    'holidays' => [
        '2025-01-01', // New Year's Day
        '2025-02-10', // St. Paul's Shipwreck
        '2025-03-19', // St. Joseph's Day
        '2025-03-31', // Freedom Day
        '2025-04-18', // Good Friday
        '2025-05-01', // Worker's Day
        '2025-06-07', // Sette Giugno
        '2025-06-29', // St. Peter & St. Paul
        '2025-08-15', // Assumption Day
        '2025-09-08', // Our Lady of Victories
        '2025-09-21', // Independence Day
        '2025-12-08', // Immaculate Conception
        '2025-12-13', // Republic Day
        '2025-12-25', // Christmas Day
    ],
    'cancellation' => [
        'business' => [
            'penalty_72h' => 0.00,
            'penalty_48h' => 0.25,
            'penalty_24h' => 0.50,
            'penalty_12h' => 0.75,
            'penalty_0h' => 1.00,
        ],
        'worker_compensation_share' => 0.50, // 50% of penalty
    ],
];
