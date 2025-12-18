<?php

/**
 * WKR-001: KYC Configuration
 *
 * Configuration for KYC (Know Your Customer) verification system.
 * Supports multiple providers: manual review, Onfido, Jumio, Veriff.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Active KYC Provider
    |--------------------------------------------------------------------------
    |
    | The default provider for KYC verification. Options:
    | - 'manual': Human admin review (default)
    | - 'onfido': Onfido identity verification
    | - 'jumio': Jumio NetVerify
    | - 'veriff': Veriff identity verification
    |
    */
    'provider' => env('KYC_PROVIDER', 'manual'),

    /*
    |--------------------------------------------------------------------------
    | Provider Configurations
    |--------------------------------------------------------------------------
    |
    | API credentials and settings for each KYC provider.
    |
    */
    'providers' => [
        'onfido' => [
            'api_key' => env('ONFIDO_API_KEY'),
            'api_url' => env('ONFIDO_API_URL', 'https://api.onfido.com/v3.6'),
            'webhook_secret' => env('ONFIDO_WEBHOOK_SECRET'),
            'sandbox' => env('ONFIDO_SANDBOX', true),
        ],

        'jumio' => [
            'api_key' => env('JUMIO_API_KEY'),
            'api_secret' => env('JUMIO_API_SECRET'),
            'api_url' => env('JUMIO_API_URL', 'https://netverify.com/api/v4'),
            'webhook_secret' => env('JUMIO_WEBHOOK_SECRET'),
        ],

        'veriff' => [
            'api_key' => env('VERIFF_API_KEY'),
            'api_secret' => env('VERIFF_API_SECRET'),
            'api_url' => env('VERIFF_API_URL', 'https://stationapi.veriff.com/v1'),
            'webhook_secret' => env('VERIFF_WEBHOOK_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Accepted Document Types
    |--------------------------------------------------------------------------
    |
    | Document types accepted for KYC verification.
    |
    */
    'document_types' => [
        'passport',
        'drivers_license',
        'national_id',
        'residence_permit',
    ],

    /*
    |--------------------------------------------------------------------------
    | Selfie Requirement
    |--------------------------------------------------------------------------
    |
    | Whether a selfie/facial photo is required for verification.
    | This enables face matching with the document photo.
    |
    */
    'selfie_required' => env('KYC_SELFIE_REQUIRED', true),

    /*
    |--------------------------------------------------------------------------
    | Document Expiry Warning
    |--------------------------------------------------------------------------
    |
    | Number of days before document expiry to send warning notifications.
    |
    */
    'expiry_warning_days' => env('KYC_EXPIRY_WARNING_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Auto Expiration
    |--------------------------------------------------------------------------
    |
    | Number of days after which an approved KYC verification automatically
    | expires and requires re-verification.
    |
    */
    'auto_expire_days' => env('KYC_AUTO_EXPIRE_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Maximum Retry Attempts
    |--------------------------------------------------------------------------
    |
    | Maximum number of verification attempts allowed before requiring
    | manual support intervention.
    |
    */
    'max_attempts' => env('KYC_MAX_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Disk and path configuration for storing KYC documents.
    | Use 'private' or 's3' for secure document storage.
    |
    */
    'storage_disk' => env('KYC_STORAGE_DISK', 'private'),
    'storage_path' => 'kyc',

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    |
    | Maximum file sizes and allowed MIME types for document uploads.
    |
    */
    'upload' => [
        'max_size' => 10 * 1024, // 10 MB in kilobytes
        'allowed_mimes' => [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'application/pdf',
        ],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Country-Specific Requirements
    |--------------------------------------------------------------------------
    |
    | Override default requirements for specific countries.
    | Country codes use ISO 3166-1 alpha-2 format.
    |
    */
    'country_requirements' => [
        'US' => [
            'document_types' => ['passport', 'drivers_license'],
            'selfie_required' => true,
            'document_back_required' => true,
        ],
        'GB' => [
            'document_types' => ['passport', 'drivers_license', 'national_id'],
            'selfie_required' => true,
            'document_back_required' => true,
        ],
        'IN' => [
            'document_types' => ['passport', 'national_id', 'drivers_license'],
            'selfie_required' => true,
            'document_back_required' => true,
            'address_verification' => true, // Aadhaar requires address proof
        ],
        'NG' => [
            'document_types' => ['passport', 'national_id', 'drivers_license'],
            'selfie_required' => true,
            'document_back_required' => false,
        ],
        'ZA' => [
            'document_types' => ['passport', 'national_id', 'drivers_license'],
            'selfie_required' => true,
            'document_back_required' => true,
        ],
        'BR' => [
            'document_types' => ['passport', 'national_id', 'drivers_license'],
            'selfie_required' => true,
            'document_back_required' => true,
        ],
        'MX' => [
            'document_types' => ['passport', 'national_id', 'drivers_license'],
            'selfie_required' => true,
            'document_back_required' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | KYC Levels
    |--------------------------------------------------------------------------
    |
    | Define what constitutes each KYC level and its privileges.
    |
    */
    'levels' => [
        'none' => [
            'label' => 'Not Verified',
            'max_daily_earnings' => 0,
            'can_work' => false,
        ],
        'basic' => [
            'label' => 'Basic',
            'max_daily_earnings' => 500, // in default currency units
            'can_work' => true,
        ],
        'enhanced' => [
            'label' => 'Enhanced',
            'max_daily_earnings' => 2000,
            'can_work' => true,
        ],
        'full' => [
            'label' => 'Fully Verified',
            'max_daily_earnings' => null, // unlimited
            'can_work' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook URLs
    |--------------------------------------------------------------------------
    |
    | Webhook endpoints for receiving provider callbacks.
    | These are relative to your app URL.
    |
    */
    'webhooks' => [
        'onfido' => '/api/webhooks/kyc/onfido',
        'jumio' => '/api/webhooks/kyc/jumio',
        'veriff' => '/api/webhooks/kyc/veriff',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Notifications
    |--------------------------------------------------------------------------
    |
    | Email addresses to notify when verifications need manual review.
    |
    */
    'admin_notifications' => [
        'enabled' => env('KYC_ADMIN_NOTIFICATIONS', true),
        'emails' => array_filter(explode(',', env('KYC_ADMIN_EMAILS', ''))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Document URL Expiry
    |--------------------------------------------------------------------------
    |
    | How long (in minutes) temporary document viewing URLs remain valid.
    |
    */
    'document_url_expiry' => 15,

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for KYC submission to prevent abuse.
    |
    */
    'rate_limits' => [
        'submissions_per_day' => 3,
        'submissions_per_hour' => 1,
    ],
];
