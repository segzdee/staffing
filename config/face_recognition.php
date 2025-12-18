<?php

/**
 * SL-005: Face Recognition Configuration
 *
 * Configuration for facial recognition during clock-in/out verification.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Face Recognition Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable face recognition for clock-in/out verification.
    | When disabled, shifts will use GPS and time-based verification only.
    |
    */
    'enabled' => env('FACE_RECOGNITION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Primary Provider
    |--------------------------------------------------------------------------
    |
    | The primary face recognition provider to use.
    | Supported: "aws", "azure", "faceplusplus"
    |
    */
    'provider' => env('FACE_RECOGNITION_PROVIDER', 'aws'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Provider
    |--------------------------------------------------------------------------
    |
    | The fallback provider to use if the primary provider fails.
    | Set to null to disable fallback to another provider.
    |
    */
    'fallback_provider' => env('FACE_RECOGNITION_FALLBACK_PROVIDER', 'azure'),

    /*
    |--------------------------------------------------------------------------
    | Minimum Confidence Threshold
    |--------------------------------------------------------------------------
    |
    | The minimum confidence score (0-100) required for a face match.
    | Recommended: 85.0 for balanced security and usability.
    |
    */
    'min_confidence' => (float) env('FACE_RECOGNITION_MIN_CONFIDENCE', 85.0),

    /*
    |--------------------------------------------------------------------------
    | Require Liveness Detection
    |--------------------------------------------------------------------------
    |
    | Whether to require liveness detection (anti-spoofing) during verification.
    | Recommended: true for production environments.
    |
    */
    'require_liveness' => env('FACE_RECOGNITION_REQUIRE_LIVENESS', true),

    /*
    |--------------------------------------------------------------------------
    | Fallback to Manual Verification
    |--------------------------------------------------------------------------
    |
    | When face recognition fails, allow manual verification by supervisors.
    | This provides a fallback for edge cases and service outages.
    |
    */
    'fallback_to_manual' => env('FACE_RECOGNITION_FALLBACK_MANUAL', true),

    /*
    |--------------------------------------------------------------------------
    | Maximum Retry Attempts
    |--------------------------------------------------------------------------
    |
    | Maximum number of verification attempts before requiring manual intervention.
    |
    */
    'max_retries' => (int) env('FACE_RECOGNITION_MAX_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Enrollment Settings
    |--------------------------------------------------------------------------
    |
    | Settings for face enrollment process.
    |
    */
    'enrollment' => [
        'require_multiple_photos' => env('FACE_ENROLLMENT_MULTIPLE_PHOTOS', false),
        'min_photo_count' => (int) env('FACE_ENROLLMENT_MIN_PHOTOS', 1),
        'max_photo_count' => (int) env('FACE_ENROLLMENT_MAX_PHOTOS', 5),
        'require_admin_approval' => env('FACE_ENROLLMENT_ADMIN_APPROVAL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | AWS Rekognition Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration specific to AWS Rekognition service.
    |
    */
    'aws' => [
        'region' => env('AWS_REKOGNITION_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        'collection_id' => env('AWS_REKOGNITION_COLLECTION', 'overtimestaff-faces'),
        'quality_filter' => env('AWS_REKOGNITION_QUALITY_FILTER', 'AUTO'), // AUTO, LOW, MEDIUM, HIGH
    ],

    /*
    |--------------------------------------------------------------------------
    | Azure Face API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration specific to Azure Face API service.
    |
    */
    'azure' => [
        'endpoint' => env('AZURE_FACE_ENDPOINT'),
        'key' => env('AZURE_FACE_KEY'),
        'person_group_id' => env('AZURE_FACE_PERSON_GROUP', 'overtimestaff'),
        'recognition_model' => env('AZURE_FACE_RECOGNITION_MODEL', 'recognition_04'),
        'detection_model' => env('AZURE_FACE_DETECTION_MODEL', 'detection_03'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Face++ Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration specific to Face++ (Megvii) service.
    |
    */
    'faceplusplus' => [
        'api_key' => env('FACEPLUSPLUS_API_KEY'),
        'api_secret' => env('FACEPLUSPLUS_API_SECRET'),
        'server' => env('FACEPLUSPLUS_SERVER', 'https://api-us.faceplusplus.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how verification images are stored.
    |
    */
    'storage' => [
        'disk' => env('FACE_RECOGNITION_STORAGE_DISK', 'public'),
        'enrollment_path' => 'face-enrollments',
        'verification_path' => 'face-verifications',
        'retain_days' => (int) env('FACE_VERIFICATION_RETAIN_DAYS', 90), // Days to keep verification images
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification Timing Settings
    |--------------------------------------------------------------------------
    |
    | Configure timing restrictions for verification.
    |
    */
    'timing' => [
        'max_processing_time_ms' => (int) env('FACE_RECOGNITION_MAX_TIME_MS', 10000), // 10 seconds max
        'retry_delay_seconds' => (int) env('FACE_RECOGNITION_RETRY_DELAY', 3), // Delay between retries
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure notifications for face recognition events.
    |
    */
    'notifications' => [
        'notify_on_enrollment_failure' => true,
        'notify_on_verification_failure' => true,
        'notify_on_suspicious_activity' => true,
        'admin_email' => env('FACE_RECOGNITION_ADMIN_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configure logging for face recognition operations.
    |
    */
    'logging' => [
        'enabled' => env('FACE_RECOGNITION_LOGGING', true),
        'log_channel' => env('FACE_RECOGNITION_LOG_CHANNEL', 'daily'),
        'log_sensitive_data' => env('FACE_RECOGNITION_LOG_SENSITIVE', false),
    ],
];
