<?php

namespace App\Http\Middleware;

use App\Services\LocalizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * GLO-006: Localization Engine - Middleware for setting application locale
 *
 * Priority order:
 * 1. URL parameter (?lang=es)
 * 2. Session locale
 * 3. Authenticated user preference
 * 4. Browser Accept-Language header
 * 5. Default config locale
 */
class SetLocaleMiddleware
{
    /**
     * The localization service instance.
     */
    protected LocalizationService $localization;

    /**
     * Create a new middleware instance.
     */
    public function __construct(LocalizationService $localization)
    {
        $this->localization = $localization;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Detect the best locale
        $locale = $this->localization->detectLocale();

        // Set the locale
        $this->localization->setLocale($locale);

        // Add locale info to request for easy access in controllers/views
        $request->attributes->set('locale', $locale);
        $request->attributes->set('is_rtl', $this->localization->isRTL($locale));
        $request->attributes->set('direction', $this->localization->getDirection($locale));

        // Share with views
        view()->share('currentLocale', $locale);
        view()->share('isRtl', $this->localization->isRTL($locale));
        view()->share('textDirection', $this->localization->getDirection($locale));
        view()->share('localeOptions', $this->localization->getLocaleOptions());

        $response = $next($request);

        // Add Content-Language header
        if (method_exists($response, 'header')) {
            $response->header('Content-Language', $locale);
        }

        return $response;
    }
}
