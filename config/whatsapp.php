<?php

/**
 * COM-004: WhatsApp Business API Configuration
 *
 * Supports multiple providers:
 * - meta: Direct WhatsApp Cloud API (recommended)
 * - twilio: Twilio WhatsApp Business API
 * - messagebird: MessageBird WhatsApp integration
 */

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Integration Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable WhatsApp messaging. When disabled, the MessagingService
    | will automatically fallback to SMS for all messages.
    |
    */
    'enabled' => env('WHATSAPP_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    |
    | The default provider to use for sending WhatsApp messages.
    | Supported: "meta", "twilio", "messagebird"
    |
    */
    'provider' => env('WHATSAPP_PROVIDER', 'meta'),

    /*
    |--------------------------------------------------------------------------
    | Meta (Facebook) WhatsApp Cloud API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the official WhatsApp Business Cloud API.
    | Get these credentials from Meta Business Suite / WhatsApp Business.
    |
    | Steps:
    | 1. Create a Meta Business account
    | 2. Set up WhatsApp Business API in Meta Business Suite
    | 3. Create a System User and generate access token
    | 4. Get your Phone Number ID from the WhatsApp setup
    |
    */
    'meta' => [
        // WhatsApp Business Phone Number ID
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

        // Meta Business Account ID (for template management)
        'business_id' => env('WHATSAPP_BUSINESS_ID'),

        // Permanent System User Access Token
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

        // App Secret (for webhook signature verification)
        'app_secret' => env('WHATSAPP_APP_SECRET'),

        // Webhook verification token (you create this)
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'overtimestaff_whatsapp_verify'),

        // Webhook URL (your endpoint for receiving status updates)
        'webhook_url' => env('WHATSAPP_WEBHOOK_URL'),

        // API version (update periodically)
        'api_version' => env('WHATSAPP_API_VERSION', 'v18.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Twilio WhatsApp Configuration
    |--------------------------------------------------------------------------
    |
    | Uses Twilio's WhatsApp Business API. Requires Twilio account with
    | WhatsApp enabled and a verified WhatsApp sender number.
    |
    | Note: Twilio credentials (sid, token) come from services.twilio
    |
    */
    'twilio' => [
        // WhatsApp-enabled phone number (with whatsapp: prefix)
        'from' => env('TWILIO_WHATSAPP_FROM'),

        // Content SID namespace for templates
        'content_namespace' => env('TWILIO_CONTENT_NAMESPACE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | MessageBird WhatsApp Configuration
    |--------------------------------------------------------------------------
    |
    | Uses MessageBird's Programmable Conversations API with WhatsApp channel.
    |
    */
    'messagebird' => [
        // API key from MessageBird dashboard
        'api_key' => env('MESSAGEBIRD_API_KEY'),

        // WhatsApp channel ID
        'channel_id' => env('MESSAGEBIRD_WHATSAPP_CHANNEL_ID'),

        // Template namespace
        'namespace' => env('MESSAGEBIRD_WHATSAPP_NAMESPACE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Templates
    |--------------------------------------------------------------------------
    |
    | Mapping of internal template names to WhatsApp template names.
    | Update these after approving templates in Meta Business Manager.
    |
    */
    'templates' => [
        'otp' => env('WHATSAPP_TEMPLATE_OTP', 'otp_verification'),
        'shift_reminder' => env('WHATSAPP_TEMPLATE_SHIFT_REMINDER', 'shift_reminder'),
        'shift_assigned' => env('WHATSAPP_TEMPLATE_SHIFT_ASSIGNED', 'shift_assigned'),
        'shift_cancelled' => env('WHATSAPP_TEMPLATE_SHIFT_CANCELLED', 'shift_cancelled'),
        'worker_checked_in' => env('WHATSAPP_TEMPLATE_WORKER_CHECKED_IN', 'worker_checked_in'),
        'payment_released' => env('WHATSAPP_TEMPLATE_PAYMENT_RELEASED', 'payment_released'),
        'urgent_alert' => env('WHATSAPP_TEMPLATE_URGENT_ALERT', 'urgent_alert'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration for WhatsApp messages.
    | WhatsApp has strict rate limits that vary by tier.
    |
    */
    'rate_limits' => [
        // Messages per second (per phone number)
        'messages_per_second' => env('WHATSAPP_RATE_LIMIT_MPS', 80),

        // Maximum messages per user per day
        'max_per_user_per_day' => env('WHATSAPP_MAX_USER_DAY', 50),

        // Maximum marketing messages per user per week
        'max_marketing_per_week' => env('WHATSAPP_MAX_MARKETING_WEEK', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for retrying failed messages.
    |
    */
    'retry' => [
        // Maximum retry attempts
        'max_attempts' => env('WHATSAPP_RETRY_MAX', 3),

        // Delay between retries in seconds (exponential backoff multiplier)
        'delay_seconds' => env('WHATSAPP_RETRY_DELAY', 60),

        // Use exponential backoff
        'exponential_backoff' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for WhatsApp operations.
    |
    */
    'logging' => [
        // Log channel for WhatsApp operations
        'channel' => env('WHATSAPP_LOG_CHANNEL', 'stack'),

        // Log all outgoing messages (for debugging)
        'log_messages' => env('WHATSAPP_LOG_MESSAGES', false),

        // Log webhook payloads
        'log_webhooks' => env('WHATSAPP_LOG_WEBHOOKS', false),
    ],

];
