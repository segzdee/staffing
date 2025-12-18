<?php

/**
 * COM-001: In-App Messaging Configuration
 *
 * Configuration options for the in-app messaging system.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Message Constraints
    |--------------------------------------------------------------------------
    */

    // Maximum message length in characters
    'max_message_length' => env('MESSAGING_MAX_LENGTH', 5000),

    // Maximum number of attachments per message
    'max_attachments' => env('MESSAGING_MAX_ATTACHMENTS', 5),

    // Maximum attachment file size in bytes (10MB default)
    'max_attachment_size' => env('MESSAGING_MAX_ATTACHMENT_SIZE', 10 * 1024 * 1024),

    // Allowed file types for attachments
    'allowed_file_types' => [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'txt',
        'csv',
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Editing
    |--------------------------------------------------------------------------
    */

    // Time limit for editing messages (in minutes)
    'edit_time_limit' => env('MESSAGING_EDIT_LIMIT', 15),

    // Whether to allow message deletion
    'allow_delete' => env('MESSAGING_ALLOW_DELETE', true),

    /*
    |--------------------------------------------------------------------------
    | Archiving & Retention
    |--------------------------------------------------------------------------
    */

    // Auto-archive conversations after this many days of inactivity
    'archive_after_days' => env('MESSAGING_ARCHIVE_DAYS', 90),

    // Delete archived conversations after this many days (null = never)
    'delete_after_days' => env('MESSAGING_DELETE_DAYS', null),

    /*
    |--------------------------------------------------------------------------
    | Real-time Broadcasting
    |--------------------------------------------------------------------------
    */

    // Broadcasting driver to use
    'broadcast_driver' => env('MESSAGING_BROADCAST_DRIVER', 'reverb'),

    // Enable typing indicators
    'typing_indicators' => env('MESSAGING_TYPING_INDICATORS', true),

    // Typing indicator timeout in seconds
    'typing_timeout' => env('MESSAGING_TYPING_TIMEOUT', 3),

    // Enable read receipts
    'read_receipts' => env('MESSAGING_READ_RECEIPTS', true),

    // Enable online status tracking
    'online_status' => env('MESSAGING_ONLINE_STATUS', true),

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    // Send push notifications for new messages
    'push_notifications' => env('MESSAGING_PUSH_NOTIFICATIONS', true),

    // Send email notifications for new messages (when offline)
    'email_notifications' => env('MESSAGING_EMAIL_NOTIFICATIONS', true),

    // Delay before sending email notification (in minutes)
    'email_delay' => env('MESSAGING_EMAIL_DELAY', 5),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    // Default number of conversations per page
    'conversations_per_page' => 20,

    // Default number of messages per page
    'messages_per_page' => 50,

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    // Maximum messages per minute per user
    'rate_limit' => env('MESSAGING_RATE_LIMIT', 30),

    /*
    |--------------------------------------------------------------------------
    | File Storage
    |--------------------------------------------------------------------------
    */

    // Storage disk for attachments
    'storage_disk' => env('MESSAGING_STORAGE_DISK', 'public'),

    // Storage path for attachments
    'storage_path' => 'message-attachments',

    /*
    |--------------------------------------------------------------------------
    | Conversation Types
    |--------------------------------------------------------------------------
    */

    'types' => [
        'direct' => [
            'name' => 'Direct Message',
            'max_participants' => 2,
            'allow_leave' => false,
        ],
        'shift' => [
            'name' => 'Shift Conversation',
            'max_participants' => 50,
            'allow_leave' => true,
        ],
        'support' => [
            'name' => 'Support Ticket',
            'max_participants' => 10,
            'allow_leave' => false,
        ],
        'broadcast' => [
            'name' => 'Broadcast',
            'max_participants' => 1000,
            'allow_leave' => true,
        ],
    ],

];
