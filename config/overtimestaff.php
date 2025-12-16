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
    | Multipliers for various surge conditions.
    |
    */
    'surge' => [
        'urgent_shift' => 0.50, // +50%
        'night_shift' => 0.30, // +30%
        'weekend' => 0.20, // +20%
        'public_holiday' => 0.50, // +50%
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
];
