<?php

namespace App\Http\Middleware;

use App\Models\WhiteLabelConfig;
use App\Services\WhiteLabelService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * WhiteLabelMiddleware
 *
 * AGY-006: White-Label Solution Middleware
 * Detects and applies white-label branding based on request domain/subdomain.
 */
class WhiteLabelMiddleware
{
    public function __construct(
        protected WhiteLabelService $whiteLabelService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if white-label is enabled
        if (! config('whitelabel.enabled', true)) {
            return $next($request);
        }

        // Get the host from the request
        $host = $request->getHost();

        // Skip for main app domain
        $mainDomain = parse_url(config('app.url'), PHP_URL_HOST);
        if ($host === $mainDomain || $host === 'localhost' || $host === '127.0.0.1') {
            return $next($request);
        }

        // Try to get white-label config for this host
        $config = $this->whiteLabelService->getConfigForHost($host);

        if ($config) {
            $this->applyWhiteLabelBranding($request, $config);
        }

        return $next($request);
    }

    /**
     * Apply white-label branding to the request.
     */
    protected function applyWhiteLabelBranding(Request $request, WhiteLabelConfig $config): void
    {
        // Store config in request for controllers to access
        $request->attributes->set('white_label_config', $config);
        $request->attributes->set('white_label_agency', $config->agency);
        $request->attributes->set('is_white_label', true);

        // Get theme variables
        $themeVariables = $this->whiteLabelService->getThemeVariables($config);

        // Generate custom CSS
        $customCss = $this->whiteLabelService->renderCustomCSS($config);

        // Share with all views
        View::share('whiteLabelConfig', $config);
        View::share('whiteLabelAgency', $config->agency);
        View::share('isWhiteLabel', true);
        View::share('whiteLabelTheme', $themeVariables);
        View::share('whiteLabelCss', $customCss);

        // Share individual branding variables for convenience
        View::share('brandName', $config->brand_name);
        View::share('brandLogo', $config->getLogoUrlOrDefault());
        View::share('brandFavicon', $config->getFaviconUrlOrDefault());
        View::share('brandPrimaryColor', $config->primary_color);
        View::share('brandSecondaryColor', $config->secondary_color);
        View::share('brandAccentColor', $config->accent_color);
        View::share('supportEmail', $config->support_email);
        View::share('supportPhone', $config->support_phone);
        View::share('hidePoweredBy', $config->hide_powered_by);

        // Share CSS variables for inline styles
        View::share('cssVariables', $config->css_variables_style);
    }
}
