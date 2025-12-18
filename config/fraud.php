<?php

/**
 * FIN-015: Fraud Detection Configuration
 *
 * Configuration for the comprehensive fraud detection system including
 * velocity limits, risk thresholds, and detection rules.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Fraud Detection Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch to enable or disable fraud detection across the application.
    | Set to false to completely bypass fraud checks (not recommended in production).
    |
    */

    'enabled' => env('FRAUD_DETECTION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Risk Score Thresholds
    |--------------------------------------------------------------------------
    |
    | Define the score ranges for each risk level. Scores are cumulative
    | from 0-100 based on various fraud signals and behaviors.
    |
    */

    'risk_thresholds' => [
        'critical' => 80, // Score >= 80 = Critical risk
        'high' => 60,     // Score >= 60 = High risk
        'medium' => 30,   // Score >= 30 = Medium risk
        'low' => 0,       // Score >= 0 = Low risk (default)
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Score Weights
    |--------------------------------------------------------------------------
    |
    | Define how much each factor contributes to the overall risk score.
    | Adjust these values based on your risk tolerance and fraud patterns.
    |
    */

    'risk_weights' => [
        // Fraud signal multiplier (severity * multiplier = points)
        'signal_multiplier' => 2,

        // Account age factors
        'new_account' => 15,       // Account < 7 days old
        'young_account' => 5,      // Account 7-30 days old

        // Profile factors
        'incomplete_profile' => 10, // Profile < 50% complete

        // Verification factors
        'unverified_email' => 10,
        'unverified_phone' => 8,
        'no_id_verification' => 15,

        // Device factors
        'extra_device' => 5, // Per device over threshold

        // Payment factors
        'failed_payment' => 5, // Per failed payment
    ],

    /*
    |--------------------------------------------------------------------------
    | Velocity Limits
    |--------------------------------------------------------------------------
    |
    | Define rate limits for various actions. When exceeded, a fraud signal
    | will be generated and the configured action will be taken.
    |
    | Format: 'action' => ['max' => count, 'period' => time_string, 'severity' => 1-10]
    |
    */

    'velocity_limits' => [
        // Signup limits
        'signup' => [
            'max' => 3,
            'period' => '24h',
            'severity' => 7,
        ],

        // Shift application limits
        'shift_application' => [
            'max' => 10,
            'period' => '1h',
            'severity' => 6,
        ],

        // Profile update limits
        'profile_update' => [
            'max' => 5,
            'period' => '1h',
            'severity' => 5,
        ],

        // Password change limits
        'password_change' => [
            'max' => 3,
            'period' => '24h',
            'severity' => 7,
        ],

        // Email change limits
        'email_change' => [
            'max' => 2,
            'period' => '24h',
            'severity' => 8,
        ],

        // Payment method limits
        'payment_method_add' => [
            'max' => 3,
            'period' => '24h',
            'severity' => 7,
        ],

        // Payment attempt limits
        'payment_attempt' => [
            'max' => 5,
            'period' => '1h',
            'severity' => 6,
        ],

        // Failed payment limits
        'failed_payment' => [
            'max' => 3,
            'period' => '24h',
            'severity' => 8,
        ],

        // Login attempt limits (in addition to standard rate limiting)
        'login_attempt' => [
            'max' => 10,
            'period' => '1h',
            'severity' => 5,
        ],

        // Device registration limits
        'devices' => [
            'max' => 5,
            'period' => '24h',
            'severity' => 6,
        ],

        // Message sending limits
        'message_send' => [
            'max' => 50,
            'period' => '1h',
            'severity' => 5,
        ],

        // Withdrawal request limits
        'withdrawal_request' => [
            'max' => 3,
            'period' => '24h',
            'severity' => 7,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Block Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum severity level that triggers automatic blocking of the action.
    | Actions with severity >= this threshold will be blocked immediately.
    |
    */

    'block_threshold' => 9,

    /*
    |--------------------------------------------------------------------------
    | Admin Notification Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum severity level that triggers admin notification.
    | Signals with severity >= this threshold will notify admins.
    |
    */

    'admin_notification_threshold' => 7,

    /*
    |--------------------------------------------------------------------------
    | Admin Notification Emails
    |--------------------------------------------------------------------------
    |
    | Email addresses that should receive fraud alert notifications.
    | These should be admin/security team members.
    |
    */

    'admin_notification_emails' => array_filter([
        env('FRAUD_ALERT_EMAIL_1'),
        env('FRAUD_ALERT_EMAIL_2'),
        env('FRAUD_ALERT_EMAIL_3'),
    ]),

    /*
    |--------------------------------------------------------------------------
    | Location Distance Threshold (km)
    |--------------------------------------------------------------------------
    |
    | Maximum distance in kilometers between login locations before
    | triggering an unusual location alert.
    |
    */

    'location_distance_threshold' => env('FRAUD_LOCATION_THRESHOLD', 500),

    /*
    |--------------------------------------------------------------------------
    | Impossible Travel Speed (km/h)
    |--------------------------------------------------------------------------
    |
    | Maximum travel speed in km/h that is considered possible.
    | Logins suggesting faster travel will trigger an alert.
    | Default: 1000 km/h (accounts for flights with some buffer)
    |
    */

    'impossible_travel_speed' => env('FRAUD_IMPOSSIBLE_TRAVEL_SPEED', 1000),

    /*
    |--------------------------------------------------------------------------
    | Sensitive Actions
    |--------------------------------------------------------------------------
    |
    | Actions that require additional verification for high-risk users.
    | These actions will trigger the verification required response.
    |
    */

    'sensitive_actions' => [
        'payment',
        'withdrawal',
        'bank_update',
        'email_change',
        'password_change',
        'phone_change',
        'two_factor_disable',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Routes
    |--------------------------------------------------------------------------
    |
    | Route patterns that require additional verification for high-risk users.
    | Supports glob-style wildcards.
    |
    */

    'sensitive_routes' => [
        'payments/*',
        'wallet/*',
        'profile/bank*',
        'settings/security/*',
        'settings/payout*',
        'api/v1/payments/*',
        'api/v1/withdrawals/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Fingerprint Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for device fingerprint tracking and analysis.
    |
    */

    'device_fingerprint' => [
        // Number of days to retain fingerprint data
        'retention_days' => 365,

        // Auto-trust devices after this many uses from the same user
        'auto_trust_threshold' => 10,

        // Maximum trusted devices per user
        'max_trusted_devices' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Score Recalculation
    |--------------------------------------------------------------------------
    |
    | Settings for automatic risk score recalculation.
    |
    */

    'risk_recalculation' => [
        // Hours before a risk score is considered stale
        'stale_hours' => 24,

        // Percentage of requests that trigger random recalculation
        'random_recalculation_percentage' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache configuration for fraud detection data.
    |
    */

    'cache' => [
        // TTL for active rules cache (seconds)
        'rules_ttl' => 300,

        // TTL for velocity counters (seconds) - should match longest period
        'velocity_ttl' => 86400,

        // Cache driver to use (null = default cache driver)
        'driver' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Logging configuration for fraud detection events.
    |
    */

    'logging' => [
        // Log channel for fraud events
        'channel' => env('FRAUD_LOG_CHANNEL', 'security'),

        // Log level for fraud signals
        'signal_level' => 'warning',

        // Log level for blocked users
        'block_level' => 'alert',

        // Log all velocity checks (verbose, disable in production)
        'log_velocity_checks' => env('FRAUD_LOG_VELOCITY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to API fraud detection.
    |
    */

    'api' => [
        // Enable fraud detection for API requests
        'enabled' => true,

        // Stricter velocity limits for API
        'strict_velocity' => true,

        // API-specific velocity multiplier
        'velocity_multiplier' => 0.5, // Half the normal limits for API
    ],

];
