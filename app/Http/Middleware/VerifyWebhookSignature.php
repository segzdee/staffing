<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify Webhook Signature Middleware
 *
 * SECURITY: Verifies webhook signatures from payment providers to prevent
 * unauthorized webhook calls. Supports multiple payment gateways.
 *
 * Supported providers:
 * - Stripe (using stripe-signature header)
 * - PayPal (using paypal-transmission-sig header)
 * - Paystack (using x-paystack-signature header)
 *
 * Usage in routes:
 *   Route::post('/webhooks/stripe', ...)->middleware('webhook.verify:stripe');
 *   Route::post('/webhooks/paypal', ...)->middleware('webhook.verify:paypal');
 *   Route::post('/webhooks/paystack', ...)->middleware('webhook.verify:paystack');
 */
class VerifyWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $provider  The payment provider (stripe, paypal, paystack)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $provider): Response
    {
        // SECURITY: In production, webhook signature verification is mandatory
        if (app()->environment('production') || config('services.webhooks.verify_signatures', true)) {
            $verified = match (strtolower($provider)) {
                'stripe' => $this->verifyStripeSignature($request),
                'paypal' => $this->verifyPayPalSignature($request),
                'paystack' => $this->verifyPaystackSignature($request),
                default => false,
            };

            if (!$verified) {
                Log::warning('Webhook signature verification failed', [
                    'provider' => $provider,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'path' => $request->path(),
                ]);

                return response()->json([
                    'error' => 'Invalid webhook signature',
                ], 401);
            }
        }

        return $next($request);
    }

    /**
     * Verify Stripe webhook signature using Stripe SDK.
     *
     * @see https://stripe.com/docs/webhooks/signatures
     */
    protected function verifyStripeSignature(Request $request): bool
    {
        $signature = $request->header('stripe-signature');
        $payload = $request->getContent();
        $secret = config('services.stripe.webhook_secret');

        if (!$signature || !$secret) {
            Log::error('Stripe webhook verification failed: missing signature or secret', [
                'has_signature' => !empty($signature),
                'has_secret' => !empty($secret),
            ]);
            return false;
        }

        try {
            // Use Stripe SDK for signature verification
            \Stripe\Webhook::constructEvent($payload, $signature, $secret);
            return true;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Stripe webhook verification error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verify PayPal webhook signature.
     *
     * PayPal uses a more complex verification involving certificate validation.
     * @see https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature
     */
    protected function verifyPayPalSignature(Request $request): bool
    {
        $transmissionId = $request->header('paypal-transmission-id');
        $transmissionTime = $request->header('paypal-transmission-time');
        $certUrl = $request->header('paypal-cert-url');
        $authAlgo = $request->header('paypal-auth-algo');
        $transmissionSig = $request->header('paypal-transmission-sig');
        $webhookId = config('services.paypal.webhook_id');

        if (!$transmissionId || !$transmissionSig || !$webhookId) {
            Log::error('PayPal webhook verification failed: missing required headers', [
                'has_transmission_id' => !empty($transmissionId),
                'has_signature' => !empty($transmissionSig),
                'has_webhook_id' => !empty($webhookId),
            ]);
            return false;
        }

        try {
            // Build the expected signature string
            // PayPal signature = base64(sha256(transmission_id|transmission_time|webhook_id|crc32(body)))
            $payload = $request->getContent();
            $crc = crc32($payload);

            // The expected signature message
            $expectedSignatureString = sprintf(
                '%s|%s|%s|%u',
                $transmissionId,
                $transmissionTime,
                $webhookId,
                $crc
            );

            // For production, verify using PayPal's API
            // This is a simplified implementation - production should use PayPal SDK
            if (app()->environment('production')) {
                return $this->verifyPayPalSignatureViaApi($request);
            }

            // For non-production, do basic header presence check
            // In production, always use API verification
            return !empty($transmissionSig);
        } catch (\Exception $e) {
            Log::error('PayPal webhook verification error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verify PayPal signature via their API (production method).
     */
    protected function verifyPayPalSignatureViaApi(Request $request): bool
    {
        try {
            $webhookId = config('services.paypal.webhook_id');
            $clientId = config('services.paypal.client_id');
            $clientSecret = config('services.paypal.secret');
            $mode = config('services.paypal.mode', 'sandbox');

            $baseUrl = $mode === 'live'
                ? 'https://api-m.paypal.com'
                : 'https://api-m.sandbox.paypal.com';

            // Get access token
            $tokenResponse = \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post("{$baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if (!$tokenResponse->successful()) {
                Log::error('PayPal token request failed', [
                    'status' => $tokenResponse->status(),
                ]);
                return false;
            }

            $accessToken = $tokenResponse->json('access_token');

            // Verify webhook signature
            $verifyResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post("{$baseUrl}/v1/notifications/verify-webhook-signature", [
                    'auth_algo' => $request->header('paypal-auth-algo'),
                    'cert_url' => $request->header('paypal-cert-url'),
                    'transmission_id' => $request->header('paypal-transmission-id'),
                    'transmission_sig' => $request->header('paypal-transmission-sig'),
                    'transmission_time' => $request->header('paypal-transmission-time'),
                    'webhook_id' => $webhookId,
                    'webhook_event' => json_decode($request->getContent(), true),
                ]);

            if ($verifyResponse->successful()) {
                $status = $verifyResponse->json('verification_status');
                return $status === 'SUCCESS';
            }

            Log::warning('PayPal webhook verification API returned failure', [
                'status' => $verifyResponse->status(),
                'body' => $verifyResponse->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('PayPal API verification error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verify Paystack webhook signature.
     *
     * Paystack uses HMAC SHA512 for signature verification.
     * @see https://paystack.com/docs/payments/webhooks/#verify-event-origin
     */
    protected function verifyPaystackSignature(Request $request): bool
    {
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        $secret = config('services.paystack.secret_key');

        if (!$signature || !$secret) {
            Log::error('Paystack webhook verification failed: missing signature or secret', [
                'has_signature' => !empty($signature),
                'has_secret' => !empty($secret),
            ]);
            return false;
        }

        try {
            // Paystack uses HMAC SHA512
            $expectedSignature = hash_hmac('sha512', $payload, $secret);

            // Use timing-safe comparison to prevent timing attacks
            $verified = hash_equals($expectedSignature, $signature);

            if (!$verified) {
                Log::warning('Paystack webhook signature mismatch');
            }

            return $verified;
        } catch (\Exception $e) {
            Log::error('Paystack webhook verification error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
