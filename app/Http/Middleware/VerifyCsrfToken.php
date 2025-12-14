<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * SECURITY NOTE: These webhook endpoints bypass CSRF protection.
     * Ensure each endpoint implements proper webhook signature verification:
     * - Stripe: Verify stripe-signature header
     * - PayPal: Verify IPN message authenticity
     * - Coinpayments: Verify HMAC signature
     * - CCBill: Verify by IP whitelist or secret key
     *
     * @var array
     */
    protected $except = [
      'stripe/webhook',           // Stripe webhook endpoint only
      'paypal/webhook',           // PayPal IPN webhook only
      'webhook/paystack',         // Paystack webhook endpoint
      'webhook/mollie',           // Mollie webhook endpoint
      'webhook/ccbill',           // CCBill webhook endpoint
      'ccbill/approved',          // CCBill approval callback
      'coinpayments/ipn'          // Coinpayments IPN endpoint
    ];
}
