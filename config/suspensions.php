<?php

/**
 * WKR-009: Worker Suspension Handling Configuration
 *
 * Configuration for the worker suspension system including:
 * - Strike expiration periods
 * - Suspension durations by category and offense count
 * - Appeal window settings
 * - Auto-suspension thresholds
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Strike Expiration
    |--------------------------------------------------------------------------
    |
    | The number of months after which strikes expire and are no longer
    | counted toward suspension escalation. Set to 0 to never expire.
    |
    */
    'strike_expiry_months' => env('SUSPENSION_STRIKE_EXPIRY_MONTHS', 12),

    /*
    |--------------------------------------------------------------------------
    | Maximum Strikes Before Permanent Ban
    |--------------------------------------------------------------------------
    |
    | The maximum number of strikes a worker can accumulate before
    | receiving a permanent ban from the platform.
    |
    */
    'max_strikes_before_permanent' => env('SUSPENSION_MAX_STRIKES', 5),

    /*
    |--------------------------------------------------------------------------
    | Suspension Duration by Category (in hours)
    |--------------------------------------------------------------------------
    |
    | Duration of suspension based on the reason category and offense number.
    | Values are in hours. Use null for indefinite suspension.
    |
    | Offense keys:
    | - '1st': First offense
    | - '2nd': Second offense
    | - '3rd': Third offense (typically escalates significantly)
    |
    */
    'duration_by_category' => [
        'no_show' => [
            '1st' => 24,      // 1 day
            '2nd' => 72,      // 3 days
            '3rd' => 168,     // 7 days
        ],
        'late_cancellation' => [
            '1st' => 12,      // 12 hours
            '2nd' => 48,      // 2 days
            '3rd' => 168,     // 7 days
        ],
        'misconduct' => [
            '1st' => 168,     // 7 days
            '2nd' => 720,     // 30 days
            '3rd' => null,    // Indefinite - requires manual review
        ],
        'policy_violation' => [
            '1st' => 48,      // 2 days
            '2nd' => 168,     // 7 days
            '3rd' => 720,     // 30 days
        ],
        'fraud' => [
            '1st' => null,    // Indefinite - serious offense
            '2nd' => null,    // Permanent consideration
            '3rd' => null,
        ],
        'safety' => [
            '1st' => 168,     // 7 days
            '2nd' => null,    // Indefinite - requires safety review
            '3rd' => null,
        ],
        'other' => [
            '1st' => 24,      // 1 day - default
            '2nd' => 72,      // 3 days
            '3rd' => 168,     // 7 days
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Appeal Window
    |--------------------------------------------------------------------------
    |
    | The number of days a worker has to submit an appeal after receiving
    | a suspension. After this window closes, appeals are not accepted.
    |
    */
    'appeal_window_days' => env('SUSPENSION_APPEAL_WINDOW_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Appeal Review SLA
    |--------------------------------------------------------------------------
    |
    | Target time (in hours) for admin to review and respond to appeals.
    | This is used for reporting and alerts, not enforced programmatically.
    |
    */
    'appeal_review_sla_hours' => env('SUSPENSION_APPEAL_SLA_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Auto-Suspension Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for automatic suspension triggers based on worker behavior.
    |
    */
    'auto_suspension' => [
        // Number of no-shows in the lookback period to trigger suspension
        'no_show_threshold' => 1,
        'no_show_lookback_days' => 90,

        // Number of late cancellations in the window to trigger suspension
        'late_cancellation_threshold' => 2,
        'late_cancellation_window_days' => 30,

        // Definition of "late" cancellation (hours before shift start)
        'late_cancellation_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Suspension Effects
    |--------------------------------------------------------------------------
    |
    | Configuration for what a suspension affects based on type.
    |
    */
    'effects' => [
        'warning' => [
            'affects_booking' => false,
            'affects_visibility' => false,
        ],
        'temporary' => [
            'affects_booking' => true,
            'affects_visibility' => false,
        ],
        'indefinite' => [
            'affects_booking' => true,
            'affects_visibility' => true,
        ],
        'permanent' => [
            'affects_booking' => true,
            'affects_visibility' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for suspension-related notifications.
    |
    */
    'notifications' => [
        // Send notification before suspension ends (hours)
        'pre_lift_hours' => 24,

        // Send reminder for pending appeals (hours)
        'appeal_reminder_hours' => 48,

        // Channels for suspension notifications
        'channels' => ['mail', 'database'],

        // Channels for admin notifications about appeals
        'admin_channels' => ['mail', 'database'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Visibility Settings
    |--------------------------------------------------------------------------
    |
    | Controls what information is visible to suspended workers.
    |
    */
    'visibility' => [
        // Show suspension reason to worker
        'show_reason' => true,

        // Show end date to worker (for temporary suspensions)
        'show_end_date' => true,

        // Show strike count to worker
        'show_strike_count' => true,

        // Show appeal status to worker
        'show_appeal_status' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for admin suspension management.
    |
    */
    'admin' => [
        // Require notes when overturning a suspension
        'require_overturn_notes' => true,

        // Require notes when denying an appeal
        'require_denial_notes' => true,

        // Allow manual suspension duration override
        'allow_duration_override' => true,

        // Maximum duration for manual suspensions (days)
        'max_manual_duration_days' => 365,
    ],

];
