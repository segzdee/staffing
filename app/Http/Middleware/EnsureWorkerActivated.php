<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureWorkerActivated Middleware
 * STAFF-REG-011: Worker Account Activation
 *
 * Ensures worker account is activated before accessing protected features.
 * Workers must complete 80% onboarding and pass activation requirements
 * before they can browse/apply for shifts.
 */
class EnsureWorkerActivated
{
    /**
     * Routes that are exempt from activation check.
     * Workers can access these routes even without activation.
     */
    protected array $exemptRoutes = [
        'worker.dashboard',
        'worker.profile.*',
        'worker.onboarding.*',
        'worker.activation.*',
        'worker.identity-verification.*',
        'worker.kyc.*',
        'worker.right-to-work.*',
        'worker.background-check.*',
        'worker.payment-setup.*',
        'worker.skills.*',
        'worker.certifications.*',
        'worker.availability.*',
        'worker.settings.*',
        'worker.support.*',
        'worker.help.*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only apply to workers
        if (!$user || $user->user_type !== 'worker') {
            return $next($request);
        }

        // Check if route is exempt
        if ($this->isExemptRoute($request)) {
            return $next($request);
        }

        $profile = $user->workerProfile;

        // Profile must exist
        if (!$profile) {
            return $this->redirectToActivation($request, 'profile_missing');
        }

        // Check if worker is activated
        if (!$profile->is_activated) {
            return $this->redirectToActivation($request, 'not_activated');
        }

        // Check if matching eligible (can be temporarily disabled by admin)
        if (!$profile->is_matching_eligible) {
            return $this->redirectToActivation($request, 'not_eligible', $profile->matching_eligibility_reason);
        }

        return $next($request);
    }

    /**
     * Check if the current route is exempt from activation check.
     */
    protected function isExemptRoute(Request $request): bool
    {
        $currentRoute = $request->route()->getName();

        if (!$currentRoute) {
            return false;
        }

        foreach ($this->exemptRoutes as $exemptPattern) {
            // Handle wildcard patterns (e.g., 'worker.profile.*')
            if (str_ends_with($exemptPattern, '.*')) {
                $prefix = substr($exemptPattern, 0, -2);
                if (str_starts_with($currentRoute, $prefix)) {
                    return true;
                }
            } elseif ($currentRoute === $exemptPattern) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirect to activation page with appropriate message.
     */
    protected function redirectToActivation(Request $request, string $reason, ?string $additionalInfo = null): Response
    {
        $messages = [
            'profile_missing' => 'Please complete your worker profile to continue.',
            'not_activated' => 'Your account is not yet activated. Please complete the activation requirements.',
            'not_eligible' => $additionalInfo ?? 'Your account is currently not eligible for shift matching.',
        ];

        $message = $messages[$reason] ?? 'Account activation required.';

        // Handle AJAX/JSON requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => $message,
                'reason' => $reason,
                'redirect_url' => route('worker.activation.index'),
                'activation_required' => true,
            ], 403);
        }

        // Redirect to activation page for web requests
        return redirect()
            ->route('worker.activation.index')
            ->with('error', $message)
            ->with('activation_reason', $reason);
    }
}
