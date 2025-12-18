<?php

namespace App\Http\Middleware;

use App\Models\DataRegion;
use App\Services\DataResidencyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * GLO-010: Data Residency System - Detect User Region Middleware
 *
 * Detects and assigns data region for authenticated users during requests.
 * Ensures users have a data residency record assigned based on their location.
 */
class DetectUserRegion
{
    protected DataResidencyService $residencyService;

    public function __construct(DataResidencyService $residencyService)
    {
        $this->residencyService = $residencyService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Skip for API requests that don't need region assignment
        if ($request->expectsJson() && ! $this->shouldProcessApiRequest($request)) {
            return $next($request);
        }

        // Check if user already has a data residency record
        if (! $user->dataResidency) {
            $this->assignRegionToUser($user, $request);
        }

        // Store region info in request for downstream use
        if ($user->dataResidency) {
            $request->attributes->set('data_region', $user->dataResidency->dataRegion);
            $request->attributes->set('data_residency', $user->dataResidency);
        }

        return $next($request);
    }

    /**
     * Assign a data region to the user based on detected location.
     */
    protected function assignRegionToUser($user, Request $request): void
    {
        try {
            // Get country from IP cache or detect
            $countryCode = $this->detectCountryCode($request);

            // Find appropriate region
            $region = $this->residencyService->getRegionForCountry($countryCode);

            if (! $region) {
                $region = DataRegion::getDefault();
            }

            if ($region) {
                // Determine if consent is required
                $requireConsent = config('data_residency.require_consent', true);

                $this->residencyService->assignDataRegion(
                    $user,
                    $region,
                    false, // Not user selected
                    ! $requireConsent // Auto-consent if not required
                );

                Log::info('Data region auto-assigned via middleware', [
                    'user_id' => $user->id,
                    'region_code' => $region->code,
                    'detected_country' => $countryCode,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to assign data region', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Detect the country code from the request.
     */
    protected function detectCountryCode(Request $request): string
    {
        $ip = $request->ip();

        // Check cache first (set by UserCountry middleware)
        $cachedCountry = Cache::get('userCountry-'.$ip);

        if ($cachedCountry) {
            return $cachedCountry;
        }

        // Try to detect from request headers (Cloudflare, etc.)
        $cfCountry = $request->header('CF-IPCountry');
        if ($cfCountry && $cfCountry !== 'XX') {
            return $cfCountry;
        }

        // Check for X-Country header (set by load balancers)
        $xCountry = $request->header('X-Country');
        if ($xCountry) {
            return $xCountry;
        }

        // Default to configured default country
        return config('data_residency.default_country', 'US');
    }

    /**
     * Determine if this API request should trigger region detection.
     */
    protected function shouldProcessApiRequest(Request $request): bool
    {
        // Always process auth-related endpoints
        $processEndpoints = [
            'api/user',
            'api/profile',
            'api/settings',
        ];

        $path = $request->path();

        foreach ($processEndpoints as $endpoint) {
            if (str_starts_with($path, $endpoint)) {
                return true;
            }
        }

        return false;
    }
}
