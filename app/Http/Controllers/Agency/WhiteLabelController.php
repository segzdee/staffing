<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\WhiteLabelDomain;
use App\Services\WhiteLabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * WhiteLabelController
 *
 * AGY-006: White-Label Solution Controller
 * Allows agencies to manage their white-label branding and domains.
 */
class WhiteLabelController extends Controller
{
    public function __construct(
        protected WhiteLabelService $whiteLabelService
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show the white-label settings page.
     */
    public function index(): View
    {
        $agency = Auth::user();
        $config = $this->whiteLabelService->getConfigByAgency($agency);

        return view('agency.white-label.index', [
            'config' => $config,
            'agency' => $agency,
            'hasConfig' => $config !== null,
        ]);
    }

    /**
     * Store a new white-label configuration.
     */
    public function store(Request $request): RedirectResponse
    {
        $agency = Auth::user();

        $validator = Validator::make($request->all(), [
            'brand_name' => 'required|string|max:255',
            'subdomain' => 'nullable|string|max:63|regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',
            'logo_url' => 'nullable|url|max:500',
            'favicon_url' => 'nullable|url|max:500',
            'primary_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'secondary_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'accent_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'support_email' => 'nullable|email|max:255',
            'support_phone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $config = $this->whiteLabelService->createConfig($agency, $request->all());

            return redirect()
                ->route('agency.white-label.index')
                ->with('success', 'White-label configuration created successfully. Your portal is available at: '.$config->subdomain_url);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Update the white-label branding.
     */
    public function update(Request $request): RedirectResponse
    {
        $agency = Auth::user();
        $config = $this->whiteLabelService->getConfigByAgency($agency);

        if (! $config) {
            return redirect()
                ->route('agency.white-label.index')
                ->withErrors(['error' => 'No white-label configuration found. Please create one first.']);
        }

        $validator = Validator::make($request->all(), [
            'brand_name' => 'required|string|max:255',
            'logo_url' => 'nullable|url|max:500',
            'favicon_url' => 'nullable|url|max:500',
            'primary_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'secondary_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'accent_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'support_email' => 'nullable|email|max:255',
            'support_phone' => 'nullable|string|max:50',
            'custom_css' => 'nullable|string|max:50000',
            'hide_powered_by' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $this->whiteLabelService->updateBranding($config, $request->all());

            return redirect()
                ->route('agency.white-label.index')
                ->with('success', 'Branding updated successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Update the subdomain.
     */
    public function updateSubdomain(Request $request): RedirectResponse
    {
        $agency = Auth::user();
        $config = $this->whiteLabelService->getConfigByAgency($agency);

        if (! $config) {
            return back()->withErrors(['error' => 'No white-label configuration found.']);
        }

        $validator = Validator::make($request->all(), [
            'subdomain' => 'required|string|max:63|regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $this->whiteLabelService->updateSubdomain($config, $request->subdomain);

            return redirect()
                ->route('agency.white-label.index')
                ->with('success', 'Subdomain updated successfully. Your new portal URL: '.$config->fresh()->subdomain_url);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['subdomain' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Setup a custom domain.
     */
    public function setupDomain(Request $request): RedirectResponse
    {
        $agency = Auth::user();
        $config = $this->whiteLabelService->getConfigByAgency($agency);

        if (! $config) {
            return back()->withErrors(['error' => 'No white-label configuration found.']);
        }

        $validator = Validator::make($request->all(), [
            'domain' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $domainRecord = $this->whiteLabelService->setupCustomDomain($config, $request->domain);

            return redirect()
                ->route('agency.white-label.domain.verify', ['domain' => $domainRecord->id])
                ->with('info', 'Domain added. Please complete verification to activate it.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['domain' => $e->getMessage()])->withInput();
        } catch (\RuntimeException $e) {
            return back()->withErrors(['domain' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Show domain verification page.
     */
    public function showDomainVerification(WhiteLabelDomain $domain): View|RedirectResponse
    {
        $agency = Auth::user();

        // Ensure domain belongs to this agency's config
        if ($domain->config->agency_id !== $agency->id) {
            abort(403, 'Unauthorized');
        }

        return view('agency.white-label.verify-domain', [
            'domain' => $domain,
            'config' => $domain->config,
            'instructions' => $domain->verification_instructions,
        ]);
    }

    /**
     * Trigger domain verification check.
     */
    public function verifyDomain(Request $request, WhiteLabelDomain $domain): JsonResponse|RedirectResponse
    {
        $agency = Auth::user();

        // Ensure domain belongs to this agency's config
        if ($domain->config->agency_id !== $agency->id) {
            abort(403, 'Unauthorized');
        }

        if (! $domain->canRetryVerification()) {
            $seconds = $domain->getSecondsUntilRetry();
            $message = "Please wait {$seconds} seconds before retrying verification.";

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 429);
            }

            return back()->withErrors(['error' => $message]);
        }

        $verified = $this->whiteLabelService->verifyDomain($domain);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $verified,
                'message' => $verified
                    ? 'Domain verified successfully!'
                    : 'Verification failed. Please check your DNS settings and try again.',
                'domain' => $domain->fresh(),
            ]);
        }

        if ($verified) {
            return redirect()
                ->route('agency.white-label.index')
                ->with('success', 'Domain verified successfully! Your custom domain is now active.');
        }

        return back()->withErrors(['error' => 'Verification failed. Please check your DNS settings and try again.']);
    }

    /**
     * Remove custom domain.
     */
    public function removeDomain(): RedirectResponse
    {
        $agency = Auth::user();
        $config = $this->whiteLabelService->getConfigByAgency($agency);

        if (! $config) {
            return back()->withErrors(['error' => 'No white-label configuration found.']);
        }

        $this->whiteLabelService->removeCustomDomain($config);

        return redirect()
            ->route('agency.white-label.index')
            ->with('success', 'Custom domain removed successfully.');
    }

    /**
     * Toggle white-label active status.
     */
    public function toggleStatus(): RedirectResponse
    {
        $agency = Auth::user();
        $config = $this->whiteLabelService->getConfigByAgency($agency);

        if (! $config) {
            return back()->withErrors(['error' => 'No white-label configuration found.']);
        }

        $this->whiteLabelService->toggleStatus($config);

        $status = $config->fresh()->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('agency.white-label.index')
            ->with('success', "White-label portal {$status} successfully.");
    }

    /**
     * Preview the white-label portal.
     */
    public function preview(): View
    {
        $agency = Auth::user();
        $config = $this->whiteLabelService->getConfigByAgency($agency);

        if (! $config) {
            return view('agency.white-label.no-config');
        }

        $themeVariables = $this->whiteLabelService->getThemeVariables($config);
        $customCss = $this->whiteLabelService->renderCustomCSS($config);

        return view('agency.white-label.preview', [
            'config' => $config,
            'themeVariables' => $themeVariables,
            'customCss' => $customCss,
        ]);
    }

    /**
     * Update email templates.
     */
    public function updateEmailTemplates(Request $request): RedirectResponse
    {
        $agency = Auth::user();
        $config = $this->whiteLabelService->getConfigByAgency($agency);

        if (! $config) {
            return back()->withErrors(['error' => 'No white-label configuration found.']);
        }

        $validator = Validator::make($request->all(), [
            'email_templates' => 'nullable|array',
            'email_templates.*.header' => 'nullable|string|max:5000',
            'email_templates.*.footer' => 'nullable|string|max:5000',
            'email_templates.*.logo_url' => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->whiteLabelService->updateBranding($config, [
            'email_templates' => $request->email_templates,
        ]);

        return redirect()
            ->route('agency.white-label.index')
            ->with('success', 'Email templates updated successfully.');
    }

    /**
     * Get white-label CSS (for API/AJAX).
     */
    public function getCss(): JsonResponse
    {
        $agency = Auth::user();
        $config = $this->whiteLabelService->getConfigByAgency($agency);

        if (! $config) {
            return response()->json(['css' => ''], 404);
        }

        $css = $this->whiteLabelService->renderCustomCSS($config);

        return response()->json(['css' => $css]);
    }

    /**
     * Delete white-label configuration.
     */
    public function destroy(): RedirectResponse
    {
        $agency = Auth::user();
        $config = $this->whiteLabelService->getConfigByAgency($agency);

        if (! $config) {
            return back()->withErrors(['error' => 'No white-label configuration found.']);
        }

        $this->whiteLabelService->deleteConfig($config);

        return redirect()
            ->route('agency.white-label.index')
            ->with('success', 'White-label configuration deleted successfully.');
    }
}
