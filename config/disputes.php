<?php

/**
 * FIN-010: Dispute Resolution System Configuration
 *
 * Configuration for the dispute resolution workflow including deadlines,
 * limits, and processing rules.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Evidence Deadline
    |--------------------------------------------------------------------------
    |
    | Number of days parties have to submit evidence after a dispute is opened.
    | After this deadline, the dispute may be resolved based on available evidence.
    |
    */
    'evidence_deadline_days' => env('DISPUTE_EVIDENCE_DEADLINE_DAYS', 5),

    /*
    |--------------------------------------------------------------------------
    | Auto Close Period
    |--------------------------------------------------------------------------
    |
    | Number of days after which stale disputes are automatically closed.
    | Disputes with no activity for this period will be auto-closed.
    |
    */
    'auto_close_days' => env('DISPUTE_AUTO_CLOSE_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Minimum Dispute Amount
    |--------------------------------------------------------------------------
    |
    | Minimum amount that can be disputed. Disputes below this amount
    | will not be accepted to prevent frivolous claims.
    |
    */
    'min_amount' => env('DISPUTE_MIN_AMOUNT', 5.00),

    /*
    |--------------------------------------------------------------------------
    | Maximum Escalation Period
    |--------------------------------------------------------------------------
    |
    | Maximum number of days before a dispute is automatically escalated
    | if it hasn't been resolved.
    |
    */
    'max_escalation_days' => env('DISPUTE_MAX_ESCALATION_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Resolution Processing Time
    |--------------------------------------------------------------------------
    |
    | Number of days allowed for processing resolution payments after
    | a dispute is resolved.
    |
    */
    'resolution_processing_days' => env('DISPUTE_RESOLUTION_PROCESSING_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Business Response Period
    |--------------------------------------------------------------------------
    |
    | Number of days a business has to respond to a dispute before
    | it can be escalated.
    |
    */
    'business_response_days' => env('DISPUTE_BUSINESS_RESPONSE_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Maximum Evidence Files
    |--------------------------------------------------------------------------
    |
    | Maximum number of evidence files each party can upload per dispute.
    |
    */
    'max_evidence_files' => env('DISPUTE_MAX_EVIDENCE_FILES', 10),

    /*
    |--------------------------------------------------------------------------
    | Maximum Evidence File Size
    |--------------------------------------------------------------------------
    |
    | Maximum file size in megabytes for each evidence file.
    |
    */
    'max_evidence_file_size_mb' => env('DISPUTE_MAX_EVIDENCE_FILE_SIZE_MB', 10),

    /*
    |--------------------------------------------------------------------------
    | Allowed Evidence File Types
    |--------------------------------------------------------------------------
    |
    | Allowed file extensions for evidence uploads.
    |
    */
    'allowed_evidence_types' => [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx',
        'txt', 'csv',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dispute Cooldown Period
    |--------------------------------------------------------------------------
    |
    | Number of hours a worker must wait after opening a dispute before
    | they can open another dispute for the same shift.
    |
    */
    'cooldown_hours' => env('DISPUTE_COOLDOWN_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Maximum Active Disputes Per User
    |--------------------------------------------------------------------------
    |
    | Maximum number of active disputes a user can have at any time.
    |
    */
    'max_active_per_user' => env('DISPUTE_MAX_ACTIVE_PER_USER', 5),

    /*
    |--------------------------------------------------------------------------
    | Split Resolution Default Percentage
    |--------------------------------------------------------------------------
    |
    | Default percentage split when resolution is "split" (50% each party).
    |
    */
    'split_default_percentage' => env('DISPUTE_SPLIT_DEFAULT_PERCENTAGE', 50),

    /*
    |--------------------------------------------------------------------------
    | Auto-Assign Threshold
    |--------------------------------------------------------------------------
    |
    | Dispute amount threshold above which disputes are automatically
    | assigned to a mediator instead of following standard workflow.
    |
    */
    'auto_assign_threshold' => env('DISPUTE_AUTO_ASSIGN_THRESHOLD', 500.00),

    /*
    |--------------------------------------------------------------------------
    | Escalation Thresholds
    |--------------------------------------------------------------------------
    |
    | Amount thresholds for different escalation levels.
    |
    */
    'escalation_thresholds' => [
        'level_1' => env('DISPUTE_ESCALATION_L1', 100.00),  // Senior Admin
        'level_2' => env('DISPUTE_ESCALATION_L2', 500.00),  // Supervisor
        'level_3' => env('DISPUTE_ESCALATION_L3', 1000.00), // Manager
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure which notifications are sent and when.
    |
    */
    'notifications' => [
        'send_email' => env('DISPUTE_SEND_EMAIL', true),
        'send_sms' => env('DISPUTE_SEND_SMS', false),
        'send_push' => env('DISPUTE_SEND_PUSH', true),

        // Reminder notifications before deadlines
        'deadline_reminder_hours' => [48, 24, 12],

        // Notify admin when dispute amount exceeds threshold
        'admin_notify_threshold' => env('DISPUTE_ADMIN_NOTIFY_THRESHOLD', 250.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | Worker Protection Rules
    |--------------------------------------------------------------------------
    |
    | Rules to protect workers from retaliatory behavior.
    |
    */
    'worker_protection' => [
        // Prevent business from seeing worker disputes in hiring decisions
        'hide_from_profile' => env('DISPUTE_HIDE_FROM_PROFILE', true),

        // Automatically flag suspicious patterns
        'flag_suspicious_patterns' => env('DISPUTE_FLAG_SUSPICIOUS', true),

        // Maximum disputes against same worker before review
        'max_disputes_against_worker' => env('DISPUTE_MAX_AGAINST_WORKER', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dispute Types Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration specific to each dispute type.
    |
    */
    'types' => [
        'payment' => [
            'priority' => 'high',
            'auto_escalate_days' => 7,
        ],
        'hours' => [
            'priority' => 'high',
            'auto_escalate_days' => 7,
        ],
        'deduction' => [
            'priority' => 'medium',
            'auto_escalate_days' => 10,
        ],
        'bonus' => [
            'priority' => 'medium',
            'auto_escalate_days' => 10,
        ],
        'expenses' => [
            'priority' => 'low',
            'auto_escalate_days' => 14,
        ],
        'other' => [
            'priority' => 'low',
            'auto_escalate_days' => 14,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SLA Configuration
    |--------------------------------------------------------------------------
    |
    | Service Level Agreement configuration for dispute resolution.
    |
    */
    'sla' => [
        // Target resolution time in hours by priority
        'resolution_targets' => [
            'urgent' => 24,
            'high' => 72,
            'medium' => 120,
            'low' => 168,
        ],

        // Warning threshold (percentage of SLA elapsed)
        'warning_threshold_percent' => 80,

        // Breach threshold (percentage of SLA elapsed)
        'breach_threshold_percent' => 100,
    ],
];
