<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        // Stripe Connect Configuration (AGY-003)
        'connect_client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'webhook_secret_connect' => env('STRIPE_WEBHOOK_SECRET_CONNECT'),
        'api_version' => '2023-10-16',
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/oauth/facebook/callback',
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/oauth/google/callback',
    ],

    'twitter' => [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/oauth/twitter/callback',
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Service Configuration (COM-004)
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'provider' => env('SMS_PROVIDER', 'twilio'), // twilio, vonage, messagebird, sns, log
    ],

    /*
    |--------------------------------------------------------------------------
    | Twilio Configuration (COM-004)
    |--------------------------------------------------------------------------
    */
    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_PHONE_NUMBER'),
        'webhook_url' => env('TWILIO_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vonage (Nexmo) Configuration (COM-004)
    |--------------------------------------------------------------------------
    */
    'vonage' => [
        'key' => env('VONAGE_API_KEY'),
        'secret' => env('VONAGE_API_SECRET'),
        'from' => env('VONAGE_FROM', 'OvertimeStaff'),
        'webhook_url' => env('VONAGE_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | MessageBird Configuration (COM-004)
    |--------------------------------------------------------------------------
    */
    'messagebird' => [
        'key' => env('MESSAGEBIRD_API_KEY'),
        'from' => env('MESSAGEBIRD_FROM', 'OvertimeStaff'),
        'webhook_url' => env('MESSAGEBIRD_WEBHOOK_URL'),
    ],

];
