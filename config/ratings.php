<?php

/**
 * WKR-004: 4-Category Rating System Configuration
 *
 * This configuration defines the weighted rating categories for workers and businesses.
 * Each category has a weight that contributes to the overall weighted score.
 * Weights must sum to 1.0 (100%) for each user type.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Worker Rating Categories
    |--------------------------------------------------------------------------
    |
    | Categories used when businesses rate workers.
    | Weights: Punctuality 25%, Quality 30%, Professionalism 25%, Reliability 20%
    |
    */
    'worker_categories' => [
        'punctuality' => [
            'weight' => 0.25,
            'label' => 'Punctuality',
            'description' => 'Arrives on time and ready to work',
            'field' => 'punctuality_rating',
        ],
        'quality' => [
            'weight' => 0.30,
            'label' => 'Work Quality',
            'description' => 'Quality of work performed',
            'field' => 'quality_rating',
        ],
        'professionalism' => [
            'weight' => 0.25,
            'label' => 'Professionalism',
            'description' => 'Professional attitude and behavior',
            'field' => 'professionalism_rating',
        ],
        'reliability' => [
            'weight' => 0.20,
            'label' => 'Reliability',
            'description' => 'Follows through on commitments',
            'field' => 'reliability_rating',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Rating Categories
    |--------------------------------------------------------------------------
    |
    | Categories used when workers rate businesses.
    | Weights: Punctuality 20%, Communication 30%, Professionalism 25%, Payment Reliability 25%
    |
    */
    'business_categories' => [
        'punctuality' => [
            'weight' => 0.20,
            'label' => 'Punctuality',
            'description' => 'Shift starts/ends as scheduled',
            'field' => 'punctuality_rating',
        ],
        'communication' => [
            'weight' => 0.30,
            'label' => 'Communication',
            'description' => 'Clear instructions and responsive communication',
            'field' => 'communication_rating',
        ],
        'professionalism' => [
            'weight' => 0.25,
            'label' => 'Professionalism',
            'description' => 'Professional work environment and treatment',
            'field' => 'professionalism_rating',
        ],
        'payment_reliability' => [
            'weight' => 0.25,
            'label' => 'Payment Reliability',
            'description' => 'Timely and accurate payment',
            'field' => 'payment_reliability_rating',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rating Scale Configuration
    |--------------------------------------------------------------------------
    */
    'min_rating' => 1,
    'max_rating' => 5,

    /*
    |--------------------------------------------------------------------------
    | Auto-Flag Threshold
    |--------------------------------------------------------------------------
    |
    | Ratings with any category below this threshold will be auto-flagged
    | for review by platform administrators.
    |
    */
    'flag_threshold' => 2,

    /*
    |--------------------------------------------------------------------------
    | Rating Deadline
    |--------------------------------------------------------------------------
    |
    | Number of days after a shift that ratings can be submitted.
    |
    */
    'deadline_days' => 14,

    /*
    |--------------------------------------------------------------------------
    | Minimum Ratings for Display
    |--------------------------------------------------------------------------
    |
    | Minimum number of ratings required before category averages are
    | publicly displayed on profiles. Below this, only overall rating is shown.
    |
    */
    'min_ratings_for_breakdown' => 3,

    /*
    |--------------------------------------------------------------------------
    | Rating Trend Configuration
    |--------------------------------------------------------------------------
    */
    'trend' => [
        'default_months' => 6,
        'max_months' => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rating Labels
    |--------------------------------------------------------------------------
    */
    'labels' => [
        1 => 'Poor',
        2 => 'Below Average',
        3 => 'Average',
        4 => 'Good',
        5 => 'Excellent',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rating Thresholds for Badges/Status
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'top_performer' => 4.5,
        'good_standing' => 3.5,
        'needs_improvement' => 2.5,
        'at_risk' => 2.0,
    ],
];
