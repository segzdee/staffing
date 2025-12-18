<?php

/**
 * GLO-001: Multi-Currency Support Configuration
 *
 * Configuration for the multi-currency system supporting global shift marketplace operations.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of ISO 4217 currency codes supported by the platform.
    | These currencies can be used for wallets, payments, and conversions.
    |
    */
    'supported' => ['EUR', 'USD', 'GBP', 'AUD', 'CAD', 'CHF', 'JPY', 'SGD', 'AED', 'NZD', 'INR', 'ZAR', 'NGN', 'KES', 'MXN', 'BRL'],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency used when no specific currency is specified.
    | This is also used as the base currency for exchange rate calculations.
    |
    */
    'default' => env('DEFAULT_CURRENCY', 'EUR'),

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Source
    |--------------------------------------------------------------------------
    |
    | The source for fetching exchange rates.
    | Options: 'ecb' (European Central Bank), 'openexchangerates'
    |
    */
    'exchange_rate_source' => env('EXCHANGE_RATE_SOURCE', 'ecb'),

    /*
    |--------------------------------------------------------------------------
    | ECB (European Central Bank) API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the ECB exchange rate API.
    | Free to use, updates daily at 16:00 CET.
    |
    */
    'ecb_api_url' => 'https://data-api.ecb.europa.eu/service/data/EXR/',

    /*
    |--------------------------------------------------------------------------
    | Open Exchange Rates API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Open Exchange Rates API.
    | Requires an API key (free tier available with limited requests).
    |
    */
    'openexchangerates_api_key' => env('OPENEXCHANGERATES_API_KEY'),
    'openexchangerates_url' => 'https://openexchangerates.org/api/',

    /*
    |--------------------------------------------------------------------------
    | Fixer.io API Configuration (Alternative)
    |--------------------------------------------------------------------------
    |
    | Alternative exchange rate provider with ECB data.
    |
    */
    'fixer_api_key' => env('FIXER_API_KEY'),
    'fixer_url' => 'http://data.fixer.io/api/',

    /*
    |--------------------------------------------------------------------------
    | Conversion Fee
    |--------------------------------------------------------------------------
    |
    | Fee percentage charged on currency conversions.
    | This helps cover exchange rate volatility and processing costs.
    |
    */
    'conversion_fee_percent' => env('CURRENCY_CONVERSION_FEE', 1.5),

    /*
    |--------------------------------------------------------------------------
    | Minimum Conversion Amount
    |--------------------------------------------------------------------------
    |
    | Minimum amount (in source currency) that can be converted.
    | Prevents micro-conversions that cost more to process than they're worth.
    |
    */
    'minimum_conversion_amount' => env('CURRENCY_MIN_CONVERSION', 10.00),

    /*
    |--------------------------------------------------------------------------
    | Currency Decimal Places
    |--------------------------------------------------------------------------
    |
    | Number of decimal places for each currency.
    | Most currencies use 2, but some (like JPY) use 0.
    |
    */
    'rounding' => [
        'EUR' => 2,
        'USD' => 2,
        'GBP' => 2,
        'AUD' => 2,
        'CAD' => 2,
        'CHF' => 2,
        'JPY' => 0,
        'SGD' => 2,
        'AED' => 2,
        'NZD' => 2,
        'INR' => 2,
        'ZAR' => 2,
        'NGN' => 2,
        'KES' => 2,
        'MXN' => 2,
        'BRL' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Symbols
    |--------------------------------------------------------------------------
    |
    | Display symbols for each supported currency.
    |
    */
    'symbols' => [
        'EUR' => "\u{20AC}", // Euro sign
        'USD' => '$',
        'GBP' => "\u{00A3}", // Pound sign
        'AUD' => 'A$',
        'CAD' => 'C$',
        'CHF' => 'CHF',
        'JPY' => "\u{00A5}", // Yen sign
        'SGD' => 'S$',
        'AED' => 'AED',
        'NZD' => 'NZ$',
        'INR' => "\u{20B9}", // Indian Rupee sign
        'ZAR' => 'R',
        'NGN' => "\u{20A6}", // Naira sign
        'KES' => 'KSh',
        'MXN' => 'MX$',
        'BRL' => 'R$',
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Names
    |--------------------------------------------------------------------------
    |
    | Full names for each supported currency.
    |
    */
    'names' => [
        'EUR' => 'Euro',
        'USD' => 'US Dollar',
        'GBP' => 'British Pound Sterling',
        'AUD' => 'Australian Dollar',
        'CAD' => 'Canadian Dollar',
        'CHF' => 'Swiss Franc',
        'JPY' => 'Japanese Yen',
        'SGD' => 'Singapore Dollar',
        'AED' => 'UAE Dirham',
        'NZD' => 'New Zealand Dollar',
        'INR' => 'Indian Rupee',
        'ZAR' => 'South African Rand',
        'NGN' => 'Nigerian Naira',
        'KES' => 'Kenyan Shilling',
        'MXN' => 'Mexican Peso',
        'BRL' => 'Brazilian Real',
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbol Position
    |--------------------------------------------------------------------------
    |
    | Whether the currency symbol appears before or after the amount.
    | true = before (e.g., $100), false = after (e.g., 100 EUR)
    |
    */
    'symbol_before' => [
        'EUR' => true,
        'USD' => true,
        'GBP' => true,
        'AUD' => true,
        'CAD' => true,
        'CHF' => false,
        'JPY' => true,
        'SGD' => true,
        'AED' => false,
        'NZD' => true,
        'INR' => true,
        'ZAR' => true,
        'NGN' => true,
        'KES' => true,
        'MXN' => true,
        'BRL' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) to cache exchange rates.
    | Default is 1 hour (3600 seconds).
    |
    */
    'cache_ttl' => env('EXCHANGE_RATE_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Rate Update Schedule
    |--------------------------------------------------------------------------
    |
    | Cron expression for when exchange rates should be updated.
    | Default is daily at 17:00 UTC (after ECB updates at 16:00 CET).
    |
    */
    'rate_update_schedule' => env('EXCHANGE_RATE_UPDATE_SCHEDULE', '0 17 * * *'),

    /*
    |--------------------------------------------------------------------------
    | Stale Rate Threshold
    |--------------------------------------------------------------------------
    |
    | Maximum age (in hours) of exchange rates before they're considered stale.
    | Stale rates will trigger warnings in the admin panel.
    |
    */
    'stale_rate_threshold_hours' => env('EXCHANGE_RATE_STALE_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Fallback Rates
    |--------------------------------------------------------------------------
    |
    | Static fallback rates to use if API calls fail.
    | These should be updated periodically and are EUR-based.
    |
    */
    'fallback_rates' => [
        'EUR' => 1.00,
        'USD' => 1.08,
        'GBP' => 0.86,
        'AUD' => 1.65,
        'CAD' => 1.47,
        'CHF' => 0.95,
        'JPY' => 162.00,
        'SGD' => 1.45,
        'AED' => 3.97,
        'NZD' => 1.78,
        'INR' => 90.00,
        'ZAR' => 20.50,
        'NGN' => 1680.00,
        'KES' => 165.00,
        'MXN' => 18.50,
        'BRL' => 5.35,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Currency Support
    |--------------------------------------------------------------------------
    |
    | Currencies supported by each payment gateway.
    | Used to route payments through the appropriate gateway.
    |
    */
    'gateway_currencies' => [
        'stripe' => ['EUR', 'USD', 'GBP', 'AUD', 'CAD', 'CHF', 'JPY', 'SGD', 'NZD', 'INR', 'MXN', 'BRL'],
        'paypal' => ['EUR', 'USD', 'GBP', 'AUD', 'CAD', 'CHF', 'JPY', 'SGD', 'NZD', 'MXN', 'BRL'],
        'paystack' => ['NGN', 'GHS', 'ZAR', 'KES', 'USD'],
        'razorpay' => ['INR'],
        'mollie' => ['EUR', 'GBP', 'USD', 'CHF'],
        'flutterwave' => ['NGN', 'GHS', 'KES', 'ZAR', 'USD', 'EUR', 'GBP'],
        'mercadopago' => ['ARS', 'BRL', 'CLP', 'COP', 'MXN', 'PEN', 'UYU', 'USD'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Regional Default Currencies
    |--------------------------------------------------------------------------
    |
    | Default currency by country/region for new user wallets.
    |
    */
    'regional_defaults' => [
        'US' => 'USD',
        'GB' => 'GBP',
        'DE' => 'EUR',
        'FR' => 'EUR',
        'ES' => 'EUR',
        'IT' => 'EUR',
        'NL' => 'EUR',
        'AU' => 'AUD',
        'CA' => 'CAD',
        'CH' => 'CHF',
        'JP' => 'JPY',
        'SG' => 'SGD',
        'AE' => 'AED',
        'NZ' => 'NZD',
        'IN' => 'INR',
        'ZA' => 'ZAR',
        'NG' => 'NGN',
        'KE' => 'KES',
        'MX' => 'MXN',
        'BR' => 'BRL',
        // Default for unlisted countries
        'default' => 'EUR',
    ],
];
