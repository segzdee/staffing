<?php

return [
    /*
    |--------------------------------------------------------------------------
    | White-Label Feature Toggle
    |--------------------------------------------------------------------------
    |
    | Enable or disable the white-label functionality globally.
    |
    */
    'enabled' => env('WHITE_LABEL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Subdomain Suffix
    |--------------------------------------------------------------------------
    |
    | The domain suffix used for agency subdomains.
    | Example: agency-name.overtimestaff.com
    |
    */
    'default_subdomain_suffix' => env('WHITE_LABEL_SUBDOMAIN_SUFFIX', '.overtimestaff.com'),

    /*
    |--------------------------------------------------------------------------
    | Allow Custom Domains
    |--------------------------------------------------------------------------
    |
    | Whether agencies can use their own custom domains.
    | Requires DNS verification before activation.
    |
    */
    'allowed_custom_domains' => env('WHITE_LABEL_CUSTOM_DOMAINS', true),

    /*
    |--------------------------------------------------------------------------
    | Custom CSS Maximum Length
    |--------------------------------------------------------------------------
    |
    | Maximum number of characters allowed for custom CSS.
    | This helps prevent abuse and performance issues.
    |
    */
    'max_custom_css_length' => env('WHITE_LABEL_MAX_CSS_LENGTH', 50000),

    /*
    |--------------------------------------------------------------------------
    | Domain Verification TTL
    |--------------------------------------------------------------------------
    |
    | Time in seconds between domain verification checks.
    | Default: 86400 (24 hours)
    |
    */
    'verification_ttl' => env('WHITE_LABEL_VERIFICATION_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | Domain Verification Expiry
    |--------------------------------------------------------------------------
    |
    | Time in days before an unverified domain verification request expires.
    | Default: 7 days
    |
    */
    'verification_expiry_days' => env('WHITE_LABEL_VERIFICATION_EXPIRY', 7),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | Time in seconds to cache white-label configurations.
    | Set to 0 to disable caching.
    |
    */
    'cache_ttl' => env('WHITE_LABEL_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Default Theme Colors
    |--------------------------------------------------------------------------
    |
    | Default color scheme used when an agency doesn't customize their portal.
    |
    */
    'default_colors' => [
        'primary' => '#3B82F6',      // Blue 500
        'secondary' => '#1E40AF',    // Blue 800
        'accent' => '#10B981',       // Emerald 500
    ],

    /*
    |--------------------------------------------------------------------------
    | Reserved Subdomains
    |--------------------------------------------------------------------------
    |
    | Subdomains that cannot be registered by agencies.
    |
    */
    'reserved_subdomains' => [
        'www',
        'app',
        'api',
        'admin',
        'mail',
        'smtp',
        'pop',
        'imap',
        'ftp',
        'ssh',
        'git',
        'cdn',
        'static',
        'assets',
        'images',
        'files',
        'upload',
        'uploads',
        'download',
        'downloads',
        'support',
        'help',
        'docs',
        'documentation',
        'blog',
        'status',
        'health',
        'demo',
        'test',
        'staging',
        'dev',
        'development',
        'sandbox',
        'beta',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Branding
    |--------------------------------------------------------------------------
    |
    | Enable white-label branding in transactional emails.
    |
    */
    'email_branding' => [
        'enabled' => env('WHITE_LABEL_EMAIL_BRANDING', true),
        'custom_header_allowed' => true,
        'custom_footer_allowed' => true,
        'max_header_length' => 5000,
        'max_footer_length' => 5000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding Options
    |--------------------------------------------------------------------------
    |
    | Available customization options for white-label portals.
    |
    */
    'branding_options' => [
        'logo' => true,
        'favicon' => true,
        'colors' => true,
        'custom_css' => true,
        'custom_js' => false,  // Disabled by default for security
        'hide_powered_by' => true,
        'support_contact' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Configuration Schema
    |--------------------------------------------------------------------------
    |
    | Extended theme configuration options available in theme_config JSON.
    |
    */
    'theme_schema' => [
        'header_background' => 'string',
        'header_text_color' => 'string',
        'footer_background' => 'string',
        'footer_text_color' => 'string',
        'button_radius' => 'string',
        'font_family' => 'string',
        'heading_font_family' => 'string',
    ],

    /*
    |--------------------------------------------------------------------------
    | SSL Certificate Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for SSL certificate provisioning for custom domains.
    | Requires a valid Let's Encrypt or similar setup on the server.
    |
    */
    'ssl' => [
        'auto_provision' => env('WHITE_LABEL_AUTO_SSL', false),
        'provider' => env('WHITE_LABEL_SSL_PROVIDER', 'letsencrypt'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    |
    | Allow agencies to add their own analytics tracking codes.
    |
    */
    'analytics' => [
        'custom_tracking_allowed' => env('WHITE_LABEL_CUSTOM_ANALYTICS', false),
        'allowed_providers' => [
            'google_analytics',
            'google_tag_manager',
            'facebook_pixel',
        ],
    ],
];
