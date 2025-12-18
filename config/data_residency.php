<?php

/**
 * GLO-010: Data Residency Configuration
 *
 * Configuration for data residency system including regional storage
 * mappings, compliance settings, and user selection options.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Region
    |--------------------------------------------------------------------------
    |
    | The default region code to use when a user's country cannot be
    | determined or doesn't match any configured region.
    |
    */
    'default_region' => env('DATA_RESIDENCY_DEFAULT_REGION', 'us'),

    /*
    |--------------------------------------------------------------------------
    | Default Country
    |--------------------------------------------------------------------------
    |
    | The default country code to use when geo-detection fails.
    |
    */
    'default_country' => env('DATA_RESIDENCY_DEFAULT_COUNTRY', 'US'),

    /*
    |--------------------------------------------------------------------------
    | Storage Disks
    |--------------------------------------------------------------------------
    |
    | Map region codes to Laravel filesystem disk names. These should be
    | configured in config/filesystems.php with appropriate S3 credentials
    | for each region.
    |
    */
    'storage_disks' => [
        'eu' => env('DATA_RESIDENCY_DISK_EU', 's3-eu'),
        'uk' => env('DATA_RESIDENCY_DISK_UK', 's3-uk'),
        'us' => env('DATA_RESIDENCY_DISK_US', 's3-us'),
        'apac' => env('DATA_RESIDENCY_DISK_APAC', 's3-apac'),
        'latam' => env('DATA_RESIDENCY_DISK_LATAM', 's3-latam'),
        'mea' => env('DATA_RESIDENCY_DISK_MEA', 's3-mea'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allow User Selection
    |--------------------------------------------------------------------------
    |
    | Whether to allow users to manually select their data region.
    | This may be required for compliance in some jurisdictions.
    |
    */
    'allow_user_selection' => env('DATA_RESIDENCY_ALLOW_USER_SELECTION', true),

    /*
    |--------------------------------------------------------------------------
    | Require Consent
    |--------------------------------------------------------------------------
    |
    | Whether to require explicit user consent before assigning a data region.
    | Required for GDPR compliance in EU regions.
    |
    */
    'require_consent' => env('DATA_RESIDENCY_REQUIRE_CONSENT', true),

    /*
    |--------------------------------------------------------------------------
    | Log All Transfers
    |--------------------------------------------------------------------------
    |
    | Whether to log all data transfers for audit purposes.
    | Recommended for compliance with GDPR and other regulations.
    |
    */
    'log_all_transfers' => env('DATA_RESIDENCY_LOG_TRANSFERS', true),

    /*
    |--------------------------------------------------------------------------
    | Auto-Assign Region
    |--------------------------------------------------------------------------
    |
    | Whether to automatically assign a data region to users on registration
    | or first login if they don't have one.
    |
    */
    'auto_assign' => env('DATA_RESIDENCY_AUTO_ASSIGN', true),

    /*
    |--------------------------------------------------------------------------
    | Cross-Region Access
    |--------------------------------------------------------------------------
    |
    | Settings for controlling cross-region data access.
    |
    */
    'cross_region' => [
        // Whether to allow cross-region data access at all
        'enabled' => env('DATA_RESIDENCY_CROSS_REGION_ENABLED', true),

        // Regions that require explicit consent for cross-region transfers
        'consent_required_from' => ['eu', 'uk'],

        // Regions considered "adequate" for GDPR transfers
        'gdpr_adequate_regions' => ['eu', 'uk', 'us'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Types
    |--------------------------------------------------------------------------
    |
    | Define which data types are subject to residency rules.
    | This affects what gets migrated during region changes.
    |
    */
    'data_types' => [
        'profile' => [
            'description' => 'User profile information',
            'includes' => ['name', 'email', 'phone', 'address'],
            'always_migrate' => true,
        ],
        'documents' => [
            'description' => 'Uploaded documents and files',
            'includes' => ['identity_documents', 'certifications', 'contracts'],
            'always_migrate' => true,
        ],
        'messages' => [
            'description' => 'Chat and communication history',
            'includes' => ['conversations', 'attachments'],
            'always_migrate' => false,
        ],
        'payments' => [
            'description' => 'Payment and financial records',
            'includes' => ['transactions', 'invoices', 'bank_details'],
            'always_migrate' => true,
        ],
        'shifts' => [
            'description' => 'Shift and work history',
            'includes' => ['assignments', 'timesheets', 'ratings'],
            'always_migrate' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Settings for notifying users about data residency changes.
    |
    */
    'notifications' => [
        // Notify user when their region is assigned
        'on_assignment' => true,

        // Notify user when their data is migrated
        'on_migration' => true,

        // Notify admin on cross-region transfers
        'admin_on_transfer' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention Settings
    |--------------------------------------------------------------------------
    |
    | Settings for data retention in source region after migration.
    |
    */
    'retention' => [
        // Days to retain data in source region after migration
        'post_migration_days' => 30,

        // Whether to create backup before migration
        'backup_before_migration' => true,
    ],
];
