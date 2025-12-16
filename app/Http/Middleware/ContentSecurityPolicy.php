<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content Security Policy Middleware with Nonce-Based Script/Style Protection
 *
 * This middleware implements a strict Content Security Policy using nonces
 * instead of 'unsafe-inline' and 'unsafe-eval', providing better XSS protection.
 *
 * Usage in Blade templates:
 *   <script nonce="{{ $cspNonce }}">
 *       // Your inline JavaScript here
 *   </script>
 *
 *   <style nonce="{{ $cspNonce }}">
 *       // Your inline CSS here
 *   </style>
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP
 * @see https://content-security-policy.com/nonce/
 */
class ContentSecurityPolicy
{
    /**
     * The CSP nonce for this request.
     *
     * @var string|null
     */
    protected ?string $nonce = null;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a cryptographically secure random nonce for this request
        $this->nonce = $this->generateNonce();

        // Share the nonce with all views
        view()->share('cspNonce', $this->nonce);

        // Process the request
        $response = $next($request);

        // Only add CSP headers to HTTP responses (not redirects, etc.)
        if (method_exists($response, 'headers')) {
            $this->addCspHeader($response);
        }

        return $response;
    }

    /**
     * Generate a cryptographically secure random nonce.
     *
     * @return string
     */
    protected function generateNonce(): string
    {
        return base64_encode(random_bytes(16));
    }

    /**
     * Get the current CSP nonce.
     *
     * @return string|null
     */
    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    /**
     * Add the Content-Security-Policy header to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    protected function addCspHeader(Response $response): void
    {
        $policy = $this->buildPolicy();

        // Use Content-Security-Policy in production, Content-Security-Policy-Report-Only in development
        // This allows testing CSP without breaking functionality during development
        if (app()->environment('production')) {
            $response->headers->set('Content-Security-Policy', $policy);
        } else {
            // In development, use Report-Only mode so violations are logged but not blocked
            // This helps identify inline scripts/styles that need nonces without breaking the app
            $response->headers->set('Content-Security-Policy-Report-Only', $policy);
        }
    }

    /**
     * Build the CSP policy string.
     *
     * @return string
     */
    protected function buildPolicy(): string
    {
        $nonce = $this->nonce;

        // Define allowed sources for various resource types
        $directives = [
            // Default policy: only allow resources from same origin
            'default-src' => ["'self'"],

            // Scripts: allow same origin + nonce + specific CDNs
            // Note: 'strict-dynamic' allows scripts loaded by trusted scripts (with nonce) to also execute
            'script-src' => [
                "'self'",
                "'nonce-{$nonce}'",
                "'strict-dynamic'",
                // CDNs for Alpine.js, etc.
                'https://cdn.jsdelivr.net',
                'https://cdnjs.cloudflare.com',
                // Google reCAPTCHA
                'https://www.google.com',
                'https://www.gstatic.com',
                // Stripe
                'https://js.stripe.com',
                // PayPal
                'https://www.paypal.com',
                'https://www.paypalobjects.com',
                // Tailwind CDN (for development fallback)
                'https://cdn.tailwindcss.com',
            ],

            // Styles: allow same origin + nonce + Google Fonts + CDNs
            'style-src' => [
                "'self'",
                "'nonce-{$nonce}'",
                // Google Fonts
                'https://fonts.googleapis.com',
                // CDNs
                'https://cdn.jsdelivr.net',
                'https://cdnjs.cloudflare.com',
            ],

            // Fonts: allow same origin + Google Fonts + CDNs
            'font-src' => [
                "'self'",
                'https://fonts.gstatic.com',
                'https://cdnjs.cloudflare.com',
                // Allow data: URIs for embedded fonts
                'data:',
            ],

            // Images: allow same origin + HTTPS + data URIs + blob URIs
            'img-src' => [
                "'self'",
                'https:',
                'data:',
                'blob:',
                // Cloudinary for media
                'https://res.cloudinary.com',
                // AWS S3
                'https://*.s3.amazonaws.com',
                // UI Avatars service
                'https://ui-avatars.com',
            ],

            // Connections (AJAX, WebSocket, etc.)
            'connect-src' => [
                "'self'",
                'https:',
                'wss:',
                // Stripe
                'https://api.stripe.com',
                // Agora (video)
                'https://*.agora.io',
                'wss://*.agora.io',
            ],

            // Frames: allow same origin + specific services
            'frame-src' => [
                "'self'",
                // Google reCAPTCHA
                'https://www.google.com',
                'https://www.recaptcha.net',
                // Stripe
                'https://js.stripe.com',
                'https://hooks.stripe.com',
                // PayPal
                'https://www.paypal.com',
                // Agora (video)
                'https://*.agora.io',
            ],

            // Frame ancestors: prevent clickjacking (same as X-Frame-Options: SAMEORIGIN)
            'frame-ancestors' => ["'self'"],

            // Object/embed: block plugins like Flash
            'object-src' => ["'none'"],

            // Base URI: restrict <base> tag to same origin
            'base-uri' => ["'self'"],

            // Form actions: restrict where forms can submit
            'form-action' => [
                "'self'",
                // Payment providers that use redirects
                'https://checkout.stripe.com',
                'https://www.paypal.com',
                'https://api.paystack.co',
                'https://api.razorpay.com',
                'https://checkout.flutterwave.com',
            ],

            // Upgrade insecure requests in production
            'upgrade-insecure-requests' => app()->environment('production') ? [] : null,

            // Block mixed content
            'block-all-mixed-content' => app()->environment('production') ? [] : null,

            // Media sources (audio/video)
            'media-src' => [
                "'self'",
                'https:',
                'blob:',
                // Cloudinary for video
                'https://res.cloudinary.com',
            ],

            // Worker scripts
            'worker-src' => [
                "'self'",
                'blob:',
            ],

            // Manifest for PWA
            'manifest-src' => ["'self'"],
        ];

        // Build the policy string
        $policyParts = [];

        foreach ($directives as $directive => $sources) {
            // Skip null directives (disabled)
            if ($sources === null) {
                continue;
            }

            // Handle directives with no values (like upgrade-insecure-requests)
            if (empty($sources)) {
                $policyParts[] = $directive;
            } else {
                $policyParts[] = $directive . ' ' . implode(' ', $sources);
            }
        }

        return implode('; ', $policyParts);
    }
}
