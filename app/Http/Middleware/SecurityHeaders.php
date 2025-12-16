<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware
 *
 * Adds essential security headers to protect against common web vulnerabilities.
 * Note: Content-Security-Policy is handled separately by ContentSecurityPolicy middleware.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers
 * @see https://securityheaders.com/
 */
class SecurityHeaders
{
    /**
     * Security headers to protect against common web vulnerabilities.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only add headers to HTTP responses (not redirects, etc.)
        if (method_exists($response, 'headers')) {
            // X-Frame-Options: Prevent clickjacking by disallowing iframe embedding
            // Note: CSP frame-ancestors is the modern replacement, but this provides fallback
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

            // X-Content-Type-Options: Prevent MIME type sniffing
            $response->headers->set('X-Content-Type-Options', 'nosniff');

            // X-XSS-Protection: Enable browser's XSS filtering (legacy but still useful)
            // Note: Modern browsers rely on CSP instead, but this provides defense-in-depth
            $response->headers->set('X-XSS-Protection', '1; mode=block');

            // Referrer-Policy: Control referrer information sent with requests
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

            // Permissions-Policy: Restrict browser features (formerly Feature-Policy)
            // This controls which browser features/APIs can be used
            $response->headers->set('Permissions-Policy', $this->buildPermissionsPolicy());

            // Strict-Transport-Security: Force HTTPS (only in production)
            // This tells browsers to only access the site over HTTPS for the specified period
            if (app()->environment('production')) {
                $response->headers->set(
                    'Strict-Transport-Security',
                    'max-age=31536000; includeSubDomains; preload'
                );
            }

            // X-Permitted-Cross-Domain-Policies: Restrict Adobe Flash/Acrobat cross-domain policies
            $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

            // Cross-Origin-Opener-Policy: Prevent cross-origin attacks
            $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

            // Cross-Origin-Embedder-Policy: Control embedding of cross-origin resources
            // Note: Using 'unsafe-none' to allow third-party resources (payment gateways, etc.)
            // Change to 'require-corp' for stricter security if cross-origin resources aren't needed
            $response->headers->set('Cross-Origin-Embedder-Policy', 'unsafe-none');

            // Cross-Origin-Resource-Policy: Control which origins can read the resource
            $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        }

        return $response;
    }

    /**
     * Build the Permissions-Policy header value.
     *
     * This restricts access to powerful browser features to prevent misuse.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Permissions-Policy
     * @see https://www.permissionspolicy.com/
     *
     * @return string
     */
    protected function buildPermissionsPolicy(): string
    {
        $policies = [
            // Camera: Disabled - not used by the application
            // Workers need camera access? Enable with: camera=(self)
            'camera' => '()',

            // Microphone: Disabled - not used by the application
            // If video calls need microphone, enable with: microphone=(self)
            'microphone' => '()',

            // Geolocation: Allow only from same origin
            // Used for GPS-based clock-in/out verification
            'geolocation' => '(self)',

            // Payment: Allow from same origin and Stripe
            // Required for Payment Request API
            'payment' => '(self "https://js.stripe.com")',

            // USB: Disabled - not used by the application
            'usb' => '()',

            // Magnetometer: Disabled - not used by the application
            'magnetometer' => '()',

            // Gyroscope: Disabled - not used by the application
            'gyroscope' => '()',

            // Accelerometer: Disabled - not used by the application
            'accelerometer' => '()',

            // Autoplay: Allow from same origin (for video content)
            'autoplay' => '(self)',

            // Fullscreen: Allow from same origin (for video players, modals)
            'fullscreen' => '(self)',

            // Picture-in-Picture: Allow from same origin
            'picture-in-picture' => '(self)',

            // Screen Wake Lock: Disabled - not used by the application
            'screen-wake-lock' => '()',

            // Document Domain: Disabled - legacy feature, security risk
            'document-domain' => '()',

            // Encrypted Media: Allow from same origin (for DRM content if needed)
            'encrypted-media' => '(self)',

            // Gamepad: Disabled - not used by the application
            'gamepad' => '()',

            // XR Spatial Tracking: Disabled - not used by the application
            'xr-spatial-tracking' => '()',

            // Clipboard Read: Allow from same origin (for copy functionality)
            'clipboard-read' => '(self)',

            // Clipboard Write: Allow from same origin (for copy functionality)
            'clipboard-write' => '(self)',

            // MIDI: Disabled - not used by the application
            'midi' => '()',

            // Battery: Disabled - privacy concern
            'battery' => '()',

            // Ambient Light Sensor: Disabled - privacy concern
            'ambient-light-sensor' => '()',

            // Display Capture: Disabled - security concern
            'display-capture' => '()',

            // Publickey Credentials (WebAuthn): Allow from same origin
            // Useful for passkey/biometric authentication if implemented
            'publickey-credentials-get' => '(self)',

            // Idle Detection: Disabled - privacy concern
            'idle-detection' => '()',

            // Serial: Disabled - not used by the application
            'serial' => '()',

            // HID: Disabled - not used by the application
            'hid' => '()',

            // Bluetooth: Disabled - not used by the application
            'bluetooth' => '()',

            // Storage Access (for cross-site cookies): Allow from same origin
            'storage-access' => '(self)',

            // Interest Cohort (FLoC): Disabled - privacy concern
            // Prevents participation in Google's FLoC tracking
            'interest-cohort' => '()',

            // Browsing Topics (Topics API): Disabled - privacy concern
            'browsing-topics' => '()',

            // Join Ad Interest Group: Disabled - privacy concern
            'join-ad-interest-group' => '()',

            // Run Ad Auction: Disabled - privacy concern
            'run-ad-auction' => '()',

            // Attribution Reporting: Disabled - privacy concern
            'attribution-reporting' => '()',

            // Compute Pressure: Disabled - not used by the application
            'compute-pressure' => '()',

            // Speaker Selection: Disabled - not used by the application
            'speaker-selection' => '()',
        ];

        // Build the policy string
        $parts = [];
        foreach ($policies as $feature => $value) {
            $parts[] = "{$feature}={$value}";
        }

        return implode(', ', $parts);
    }
}
