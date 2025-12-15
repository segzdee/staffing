<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Alerting Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for external alerting integrations (Slack, PagerDuty, Email)
    | These settings can be overridden in the admin panel.
    |
    */

    // Master switch to enable/disable all alerting
    'enabled' => env('ALERTS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Slack Configuration
    |--------------------------------------------------------------------------
    */
    'slack' => [
        'enabled' => env('SLACK_ENABLED', false),
        'webhooks' => [
            'default' => env('SLACK_WEBHOOK_URL'),
            'critical' => env('SLACK_WEBHOOK_URL_CRITICAL'),
            'warnings' => env('SLACK_WEBHOOK_URL_WARNINGS'),
        ],
        'default_channel' => env('SLACK_DEFAULT_CHANNEL', '#monitoring'),
        'mention_on_critical' => env('SLACK_MENTION_CRITICAL', '@channel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PagerDuty Configuration
    |--------------------------------------------------------------------------
    */
    'pagerduty' => [
        'enabled' => env('PAGERDUTY_ENABLED', false),
        'api_url' => 'https://events.pagerduty.com/v2/enqueue',
        'integration_key' => env('PAGERDUTY_INTEGRATION_KEY'),
        'routing_keys' => [
            'default' => env('PAGERDUTY_ROUTING_KEY'),
            'critical' => env('PAGERDUTY_ROUTING_KEY_CRITICAL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */
    'email' => [
        'enabled' => env('ALERT_EMAIL_ENABLED', true),
        'recipients' => array_filter(explode(',', env('ALERT_EMAIL_RECIPIENTS', ''))),
        'critical_recipients' => array_filter(explode(',', env('ALERT_EMAIL_CRITICAL_RECIPIENTS', ''))),
        'from_address' => env('MAIL_FROM_ADDRESS'),
        'from_name' => env('ALERT_EMAIL_FROM_NAME', 'OvertimeStaff Alerts'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Suppression Settings
    |--------------------------------------------------------------------------
    */
    'suppression' => [
        // Default cooldown period in minutes (don't resend same alert)
        'default_cooldown_minutes' => 60,

        // Maximum retry attempts for failed alerts
        'max_retries' => 3,

        // Quiet hours settings
        'quiet_hours' => [
            'enabled' => env('ALERT_QUIET_HOURS_ENABLED', false),
            'start' => env('ALERT_QUIET_HOURS_START', '22:00'),
            'end' => env('ALERT_QUIET_HOURS_END', '08:00'),
            'timezone' => env('ALERT_QUIET_HOURS_TIMEZONE', 'UTC'),
        ],

        // Digest settings (group similar alerts)
        'digest' => [
            'enabled' => true,
            'interval_hours' => 4,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Routing Rules
    |--------------------------------------------------------------------------
    |
    | Define which channels to use based on severity
    |
    */
    'routing' => [
        'critical' => ['slack', 'pagerduty', 'email'],
        'high' => ['slack', 'email'],
        'warning' => ['slack', 'email'],
        'info' => ['slack'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Thresholds
    |--------------------------------------------------------------------------
    |
    | Default thresholds for common metrics. These can be overridden in the
    | alert_configurations table.
    |
    */
    'thresholds' => [
        'api_response_time' => [
            'warning' => 1000,  // ms
            'critical' => 3000, // ms
        ],
        'payment_success_rate' => [
            'warning' => 98,   // percentage (below this is warning)
            'critical' => 95,  // percentage (below this is critical)
        ],
        'queue_depth' => [
            'warning' => 1000,  // job count
            'critical' => 5000, // job count
        ],
        'health_score' => [
            'warning' => 70,   // percentage
            'critical' => 50,  // percentage
        ],
        'error_rate' => [
            'warning' => 5,    // percentage
            'critical' => 10,  // percentage
        ],
        'disk_usage' => [
            'warning' => 80,   // percentage
            'critical' => 90,  // percentage
        ],
    ],
];
