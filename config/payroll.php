<?php

/**
 * FIN-005: Payroll Processing System Configuration
 *
 * This configuration file controls all aspects of the payroll processing system
 * including pay cycles, payment methods, approval workflows, and tax settings.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Pay Cycle
    |--------------------------------------------------------------------------
    |
    | The default pay cycle for the platform. This determines how often
    | payroll runs should typically be generated.
    |
    | Supported: "weekly", "biweekly", "monthly"
    |
    */

    'default_pay_cycle' => env('PAYROLL_PAY_CYCLE', 'weekly'),

    /*
    |--------------------------------------------------------------------------
    | Minimum Payout Amount
    |--------------------------------------------------------------------------
    |
    | The minimum net amount required for a payout to be processed.
    | Amounts below this threshold will be rolled over to the next pay period.
    |
    */

    'min_payout_amount' => env('PAYROLL_MIN_PAYOUT', 10.00),

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    |
    | Available payment methods for processing payroll disbursements.
    |
    */

    'payment_methods' => [
        'stripe' => [
            'enabled' => true,
            'name' => 'Stripe Connect',
            'description' => 'Instant payouts via Stripe Connect',
        ],
        'bank_transfer' => [
            'enabled' => true,
            'name' => 'Bank Transfer',
            'description' => 'Standard bank transfer (2-5 business days)',
        ],
        'check' => [
            'enabled' => false,
            'name' => 'Check',
            'description' => 'Physical check mailed to worker',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Approval Workflow
    |--------------------------------------------------------------------------
    |
    | Configuration for the payroll approval workflow.
    |
    */

    'require_approval' => env('PAYROLL_REQUIRE_APPROVAL', true),

    'require_different_approver' => env('PAYROLL_REQUIRE_DIFFERENT_APPROVER', true),

    'auto_process_approved' => env('PAYROLL_AUTO_PROCESS', false),

    /*
    |--------------------------------------------------------------------------
    | Platform Fees
    |--------------------------------------------------------------------------
    |
    | Platform fee configuration for payroll deductions.
    |
    */

    'platform_fee_rate' => env('PAYROLL_PLATFORM_FEE_RATE', 10.0), // Percentage

    /*
    |--------------------------------------------------------------------------
    | Tax Settings
    |--------------------------------------------------------------------------
    |
    | Default tax configuration. Actual rates are typically pulled from
    | the TaxJurisdictionService based on worker location.
    |
    */

    'default_tax_rate' => env('PAYROLL_DEFAULT_TAX_RATE', 0), // Percentage

    'tax_reporting_enabled' => env('PAYROLL_TAX_REPORTING', true),

    /*
    |--------------------------------------------------------------------------
    | Overtime Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for overtime pay calculations.
    |
    */

    'overtime_multiplier' => env('PAYROLL_OVERTIME_MULTIPLIER', 1.5),

    'weekly_overtime_threshold' => env('PAYROLL_WEEKLY_OVERTIME_HOURS', 40),

    'daily_overtime_threshold' => env('PAYROLL_DAILY_OVERTIME_HOURS', 8),

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    |
    | Default currency for payroll processing.
    |
    */

    'currency' => env('PAYROLL_CURRENCY', 'usd'),

    'currency_symbol' => env('PAYROLL_CURRENCY_SYMBOL', '$'),

    'currency_decimal_places' => 2,

    /*
    |--------------------------------------------------------------------------
    | Paystub Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for paystub generation and display.
    |
    */

    'paystub' => [
        'show_deduction_details' => true,
        'show_year_to_date' => true,
        'pdf_enabled' => true,
        'logo_path' => null, // Path to company logo for paystubs
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for payroll data exports.
    |
    */

    'export' => [
        'formats' => ['csv', 'json'],
        'include_worker_details' => true,
        'include_shift_details' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for payroll-related notifications.
    |
    */

    'notifications' => [
        'approval_required' => true,
        'payment_processed' => true,
        'paystub_available' => true,
        'payment_failed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Schedule
    |--------------------------------------------------------------------------
    |
    | Schedule configuration for automated payroll processing.
    |
    */

    'schedule' => [
        'auto_generate_enabled' => env('PAYROLL_AUTO_GENERATE', false),
        'auto_generate_day' => env('PAYROLL_AUTO_GENERATE_DAY', 'monday'), // Day of week
        'auto_generate_time' => env('PAYROLL_AUTO_GENERATE_TIME', '00:00'),
        'processing_window_hours' => 48, // Hours after pay date to complete processing
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for payment retry logic.
    |
    */

    'retry' => [
        'max_attempts' => 3,
        'delay_minutes' => 60,
        'notify_on_failure' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for payroll audit trail and compliance.
    |
    */

    'audit' => [
        'enabled' => true,
        'retention_days' => 2555, // ~7 years for tax compliance
        'log_all_changes' => true,
    ],

];
