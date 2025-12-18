<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | COM-002: Push Notifications via Firebase Cloud Messaging (FCM)
    |
    | This configuration file contains all Firebase-related settings for
    | push notifications. The credentials file should be downloaded from
    | the Firebase Console and stored securely.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | Your Firebase project ID, found in the Firebase Console under
    | Project Settings > General.
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | Path to your Firebase service account credentials JSON file.
    | This file should be downloaded from the Firebase Console under
    | Project Settings > Service Accounts > Generate new private key.
    |
    | The file should be stored in storage/app/ and be gitignored.
    |
    */

    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-credentials.json')),
    ],

    /*
    |--------------------------------------------------------------------------
    | FCM (Firebase Cloud Messaging) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Cloud Messaging (push notifications).
    |
    */

    'fcm' => [
        // Enable/disable FCM
        'enabled' => env('FCM_ENABLED', true),

        // FCM HTTP v1 API endpoint
        'api_url' => 'https://fcm.googleapis.com/v1/projects/{project}/messages:send',

        // Default notification settings
        'default_sound' => 'default',
        'default_icon' => env('FCM_DEFAULT_ICON', 'ic_notification'),

        // Android-specific settings
        'android' => [
            'priority' => 'high',
            'notification_priority' => 'PRIORITY_HIGH',
            'default_channel_id' => env('FCM_ANDROID_CHANNEL_ID', 'overtimestaff_default'),
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ],

        // iOS/APNs-specific settings (sent through FCM)
        'apns' => [
            'sound' => 'default',
            'badge' => true,
            'content_available' => true,
        ],

        // Web Push settings
        'webpush' => [
            'icon' => env('FCM_WEB_ICON', '/images/notification-icon.png'),
            'badge' => env('FCM_WEB_BADGE', '/images/notification-badge.png'),
        ],

        // Rate limiting
        'rate_limit' => [
            'enabled' => env('FCM_RATE_LIMIT_ENABLED', true),
            'max_per_user_per_minute' => env('FCM_MAX_PER_USER_PER_MINUTE', 10),
            'max_bulk_per_minute' => env('FCM_MAX_BULK_PER_MINUTE', 500),
        ],

        // Retry configuration
        'retry' => [
            'enabled' => env('FCM_RETRY_ENABLED', true),
            'max_attempts' => env('FCM_MAX_RETRY_ATTEMPTS', 3),
            'delay_seconds' => env('FCM_RETRY_DELAY', 5),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | APNs (Apple Push Notification Service) Configuration
    |--------------------------------------------------------------------------
    |
    | Direct APNs configuration (if not using FCM for iOS).
    | Typically FCM handles both Android and iOS, but direct APNs
    | can be used for better control over iOS-specific features.
    |
    */

    'apns' => [
        // Enable direct APNs (usually false when using FCM)
        'enabled' => env('APNS_ENABLED', false),

        // APNs environment: 'production' or 'sandbox'
        'environment' => env('APNS_ENVIRONMENT', 'production'),

        // Bundle ID (your app's bundle identifier)
        'bundle_id' => env('APNS_BUNDLE_ID'),

        // APNs authentication key (.p8 file)
        'key_file' => env('APNS_KEY_FILE', storage_path('app/apns-auth-key.p8')),
        'key_id' => env('APNS_KEY_ID'),
        'team_id' => env('APNS_TEAM_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Push Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for browser-based web push notifications.
    |
    */

    'webpush' => [
        // Enable web push
        'enabled' => env('WEBPUSH_ENABLED', true),

        // VAPID keys for web push authentication
        'vapid' => [
            'subject' => env('VAPID_SUBJECT', env('APP_URL')),
            'public_key' => env('VAPID_PUBLIC_KEY'),
            'private_key' => env('VAPID_PRIVATE_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Management
    |--------------------------------------------------------------------------
    |
    | Settings for managing push notification tokens.
    |
    */

    'tokens' => [
        // Days of inactivity before a token is considered stale
        'inactive_days' => env('PUSH_TOKEN_INACTIVE_DAYS', 90),

        // Maximum tokens per user (to prevent abuse)
        'max_per_user' => env('PUSH_TOKEN_MAX_PER_USER', 10),

        // Automatically remove invalid tokens on send failure
        'auto_remove_invalid' => env('PUSH_TOKEN_AUTO_REMOVE_INVALID', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for logging push notification activity.
    |
    */

    'logging' => [
        // Enable detailed logging
        'enabled' => env('PUSH_LOGGING_ENABLED', true),

        // Log channel to use
        'channel' => env('PUSH_LOG_CHANNEL', 'stack'),

        // Log successful sends (can be verbose)
        'log_success' => env('PUSH_LOG_SUCCESS', false),

        // Log all failures
        'log_failures' => env('PUSH_LOG_FAILURES', true),

        // Days to retain logs in database
        'retention_days' => env('PUSH_LOG_RETENTION_DAYS', 30),
    ],

];
