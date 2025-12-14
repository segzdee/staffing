<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * Security headers to protect against common web vulnerabilities.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only add headers to HTTP responses (not redirects, etc.)
        if (method_exists($response, 'headers')) {
            // X-Frame-Options: Prevent clickjacking by disallowing iframe embedding
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

            // X-Content-Type-Options: Prevent MIME type sniffing
            $response->headers->set('X-Content-Type-Options', 'nosniff');

            // X-XSS-Protection: Enable browser's XSS filtering (legacy but still useful)
            $response->headers->set('X-XSS-Protection', '1; mode=block');

            // Referrer-Policy: Control referrer information sent with requests
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

            // Permissions-Policy: Restrict browser features
            $response->headers->set('Permissions-Policy', 'geolocation=(self), microphone=(), camera=()');

            // Strict-Transport-Security: Force HTTPS (only in production)
            if (app()->environment('production')) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }

            // Content-Security-Policy: Basic CSP (customize as needed)
            // Note: This is a permissive policy - tighten in production
            if (app()->environment('production')) {
                $response->headers->set(
                    'Content-Security-Policy',
                    "default-src 'self'; " .
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.google.com https://www.gstatic.com; " .
                    "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
                    "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
                    "img-src 'self' data: https: blob:; " .
                    "connect-src 'self' https:; " .
                    "frame-src 'self' https://www.google.com https://www.recaptcha.net; " .
                    "frame-ancestors 'self';"
                );
            }
        }

        return $response;
    }
}
