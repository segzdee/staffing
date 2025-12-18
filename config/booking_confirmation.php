<?php

/**
 * SL-004: Booking Confirmation System Configuration
 *
 * Manages settings for the dual-confirmation workflow where both
 * workers and businesses must confirm shift bookings.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Confirmation Expiry
    |--------------------------------------------------------------------------
    |
    | The number of hours after which an unconfirmed booking will expire.
    | After this period, the booking slot will be released.
    |
    */
    'expiry_hours' => env('BOOKING_CONFIRMATION_EXPIRY_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Reminder Hours Before Expiry
    |--------------------------------------------------------------------------
    |
    | Array of hours before expiry when reminders should be sent.
    | E.g., [12, 4] means reminders at 12 hours and 4 hours before expiry.
    |
    */
    'reminder_hours_before' => [12, 4],

    /*
    |--------------------------------------------------------------------------
    | Auto-Confirm Returning Workers
    |--------------------------------------------------------------------------
    |
    | When enabled, returning workers (who have successfully completed
    | shifts with the same business before) will be auto-confirmed on
    | the business side after worker confirmation.
    |
    */
    'auto_confirm_returning_workers' => env('AUTO_CONFIRM_RETURNING_WORKERS', true),

    /*
    |--------------------------------------------------------------------------
    | Minimum Completed Shifts for Auto-Confirm
    |--------------------------------------------------------------------------
    |
    | The minimum number of completed shifts a worker must have with a
    | business before auto-confirmation is enabled.
    |
    */
    'auto_confirm_min_shifts' => env('AUTO_CONFIRM_MIN_SHIFTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Minimum Rating for Auto-Confirm
    |--------------------------------------------------------------------------
    |
    | The minimum average rating a worker must have from the business
    | to qualify for auto-confirmation.
    |
    */
    'auto_confirm_min_rating' => env('AUTO_CONFIRM_MIN_RATING', 4.0),

    /*
    |--------------------------------------------------------------------------
    | Require Business Confirmation
    |--------------------------------------------------------------------------
    |
    | When enabled, businesses must actively confirm each booking.
    | When disabled, bookings are auto-confirmed on business side
    | after worker confirmation.
    |
    */
    'require_business_confirmation' => env('REQUIRE_BUSINESS_CONFIRMATION', true),

    /*
    |--------------------------------------------------------------------------
    | Confirmation Code Length
    |--------------------------------------------------------------------------
    |
    | The length of the unique confirmation code generated for each booking.
    | Used for QR codes and quick lookups.
    |
    */
    'code_length' => 8,

    /*
    |--------------------------------------------------------------------------
    | Expiring Soon Threshold
    |--------------------------------------------------------------------------
    |
    | The number of hours before expiry when a confirmation is considered
    | "expiring soon" for UI highlighting.
    |
    */
    'expiring_soon_hours' => 4,

    /*
    |--------------------------------------------------------------------------
    | QR Code Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for QR code generation.
    |
    */
    'qr_code' => [
        'size' => 300, // QR code size in pixels
        'format' => 'png', // Output format: png, svg
        'error_correction' => 'M', // Error correction level: L, M, Q, H
        'foreground_color' => '#000000',
        'background_color' => '#FFFFFF',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Which channels to use for confirmation notifications.
    | Available: 'database', 'mail', 'sms', 'push'
    |
    */
    'notification_channels' => [
        'pending' => ['database', 'mail', 'push'],
        'confirmed' => ['database', 'mail', 'push'],
        'declined' => ['database', 'mail'],
        'reminder' => ['database', 'mail', 'push'],
        'expired' => ['database', 'mail'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Bulk Confirmation
    |--------------------------------------------------------------------------
    |
    | Settings for bulk confirmation actions by businesses.
    |
    */
    'bulk_confirmation' => [
        // Maximum number of confirmations that can be bulk-confirmed at once
        'max_per_batch' => 50,

        // Allow bulk decline with single reason
        'allow_bulk_decline' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Shift Status Integration
    |--------------------------------------------------------------------------
    |
    | How to update shift status when confirmation state changes.
    |
    */
    'shift_integration' => [
        // Update shift.filled_workers on full confirmation
        'update_filled_count' => true,

        // Release slot back to market on decline/expiry
        'release_on_decline' => true,

        // Trigger next worker in queue on decline/expiry
        'process_waitlist_on_release' => true,

        // Mark assignment as confirmed on full confirmation
        'confirm_assignment' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Tracking
    |--------------------------------------------------------------------------
    |
    | Settings for confirmation analytics.
    |
    */
    'analytics' => [
        // Track confirmation times for performance metrics
        'track_response_times' => true,

        // Track decline reasons for insights
        'track_decline_reasons' => true,

        // Generate weekly confirmation report
        'weekly_report' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Confirmation Policies
    |--------------------------------------------------------------------------
    |
    | Business rules for the confirmation process.
    |
    */
    'policies' => [
        // Allow workers to cancel after confirming (before shift starts)
        'allow_worker_cancel_after_confirm' => true,

        // Grace period (hours) after confirming where cancel has no penalty
        'cancel_grace_period_hours' => 12,

        // Allow businesses to reassign if worker doesn't confirm
        'allow_reassign_on_no_confirm' => true,

        // Extend expiry when one party confirms (to give other party more time)
        'extend_expiry_on_partial_confirm' => false,
        'extension_hours' => 4,
    ],
];
