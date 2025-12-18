<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Provider
    |--------------------------------------------------------------------------
    |
    | The email provider used for sending emails. This affects how webhooks
    | are processed and what features are available.
    |
    | Supported: "smtp", "sendgrid", "mailgun", "ses"
    |
    */

    'provider' => env('MAIL_PROVIDER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Open Tracking
    |--------------------------------------------------------------------------
    |
    | Enable or disable tracking of email opens. When enabled, a tracking
    | pixel will be inserted into HTML emails.
    |
    */

    'track_opens' => env('EMAIL_TRACK_OPENS', true),

    /*
    |--------------------------------------------------------------------------
    | Click Tracking
    |--------------------------------------------------------------------------
    |
    | Enable or disable tracking of link clicks. When enabled, links in
    | emails will be wrapped for tracking purposes.
    |
    */

    'track_clicks' => env('EMAIL_TRACK_CLICKS', true),

    /*
    |--------------------------------------------------------------------------
    | From Name
    |--------------------------------------------------------------------------
    |
    | The default name that will appear in the "From" field of emails.
    |
    */

    'from_name' => env('MAIL_FROM_NAME', 'OvertimeStaff'),

    /*
    |--------------------------------------------------------------------------
    | From Email
    |--------------------------------------------------------------------------
    |
    | The default email address that will appear in the "From" field.
    |
    */

    'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@overtimestaff.com'),

    /*
    |--------------------------------------------------------------------------
    | Reply-To Email
    |--------------------------------------------------------------------------
    |
    | The default reply-to email address for all outgoing emails.
    |
    */

    'reply_to' => env('MAIL_REPLY_TO', 'support@overtimestaff.com'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secrets
    |--------------------------------------------------------------------------
    |
    | Secrets for validating webhooks from email providers.
    |
    */

    'webhook_secrets' => [
        'sendgrid' => env('SENDGRID_WEBHOOK_SECRET'),
        'mailgun' => env('MAILGUN_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Template Variables
    |--------------------------------------------------------------------------
    |
    | Variables that are automatically available in all email templates.
    |
    */

    'default_variables' => [
        'app_name',
        'app_url',
        'user_name',
        'user_email',
        'user_first_name',
        'current_year',
        'unsubscribe_url',
        'preferences_url',
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Categories
    |--------------------------------------------------------------------------
    |
    | Available categories for email templates and their descriptions.
    |
    */

    'categories' => [
        'transactional' => [
            'label' => 'Transactional',
            'description' => 'Essential emails like password resets and confirmations (always sent)',
        ],
        'marketing' => [
            'label' => 'Marketing',
            'description' => 'Promotional content, newsletters, and special offers',
        ],
        'notification' => [
            'label' => 'Notification',
            'description' => 'Shift updates, applications, and assignments',
        ],
        'reminder' => [
            'label' => 'Reminder',
            'description' => 'Shift reminders and tips for users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bounce Handling
    |--------------------------------------------------------------------------
    |
    | Configuration for handling bounced emails.
    |
    */

    'bounce_handling' => [
        // Number of bounces before marking email as invalid
        'threshold' => env('EMAIL_BOUNCE_THRESHOLD', 3),

        // Auto-unsubscribe on hard bounce
        'auto_unsubscribe' => env('EMAIL_AUTO_UNSUBSCRIBE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to retain email logs before cleanup.
    |
    */

    'log_retention_days' => env('EMAIL_LOG_RETENTION_DAYS', 90),

];
