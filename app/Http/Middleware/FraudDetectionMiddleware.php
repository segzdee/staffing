<?php

namespace App\Http\Middleware;

use App\Models\UserRiskScore;
use App\Services\FraudDetectionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * FIN-015: Fraud Detection Middleware
 *
 * Performs fraud detection checks on sensitive actions including:
 * - Device fingerprint tracking
 * - Velocity checks for specific actions
 * - Risk score evaluation
 * - Blocking of high-risk users
 */
class FraudDetectionMiddleware
{
    /**
     * The fraud detection service.
     */
    protected FraudDetectionService $fraudService;

    /**
     * Create a new middleware instance.
     */
    public function __construct(FraudDetectionService $fraudService)
    {
        $this->fraudService = $fraudService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  string|null  $action  The action type for velocity checking
     */
    public function handle(Request $request, Closure $next, ?string $action = null): Response
    {
        // Skip fraud checks if disabled in config
        if (! config('fraud.enabled', true)) {
            return $next($request);
        }

        $user = $request->user();

        // Skip for unauthenticated requests (handled separately at registration)
        if (! $user) {
            return $next($request);
        }

        try {
            // Check if user is blocked
            if ($this->isUserBlocked($user)) {
                return $this->blockedResponse($request);
            }

            // Record device fingerprint
            $fingerprint = $this->fraudService->recordDeviceFingerprint($user, $request);

            // Check if device is blocked
            if ($fingerprint->is_blocked) {
                Log::warning('Blocked device access attempt', [
                    'user_id' => $user->id,
                    'fingerprint' => substr($fingerprint->fingerprint_hash, 0, 16).'...',
                    'ip' => $request->ip(),
                ]);

                return $this->blockedDeviceResponse($request);
            }

            // Check velocity for specific action if provided
            if ($action) {
                $signal = $this->fraudService->checkVelocity($user, $action);

                if ($signal && $signal->severity >= config('fraud.block_threshold', 9)) {
                    return $this->rateLimitedResponse($request, $action);
                }
            }

            // Check user risk level
            $riskScore = UserRiskScore::where('user_id', $user->id)->first();

            if ($riskScore && $riskScore->isCritical()) {
                // Critical risk users may need additional verification
                if ($this->requiresAdditionalVerification($request, $action)) {
                    return $this->verificationRequiredResponse($request);
                }
            }

            // Run periodic anomaly detection (1% of requests)
            if (mt_rand(1, 100) === 1) {
                $this->fraudService->detectAnomalies($user);
            }

        } catch (\Exception $e) {
            // Log error but don't block the request on fraud check failure
            Log::error('Fraud detection middleware error', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $next($request);
    }

    /**
     * Check if user is blocked.
     */
    protected function isUserBlocked($user): bool
    {
        return $user->status === 'suspended' ||
               $user->status === 'banned' ||
               $user->is_blocked === true;
    }

    /**
     * Check if action requires additional verification for high-risk users.
     */
    protected function requiresAdditionalVerification(Request $request, ?string $action): bool
    {
        $sensitiveActions = config('fraud.sensitive_actions', [
            'payment',
            'withdrawal',
            'bank_update',
            'email_change',
            'password_change',
        ]);

        if (in_array($action, $sensitiveActions)) {
            return true;
        }

        // Check for sensitive routes
        $sensitiveRoutes = config('fraud.sensitive_routes', [
            'payments/*',
            'wallet/*',
            'profile/bank',
            'settings/security',
        ]);

        $path = $request->path();
        foreach ($sensitiveRoutes as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return blocked user response.
     */
    protected function blockedResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'account_suspended',
                'message' => 'Your account has been suspended. Please contact support.',
            ], 403);
        }

        return redirect()->route('account.suspended')
            ->with('error', 'Your account has been suspended. Please contact support.');
    }

    /**
     * Return blocked device response.
     */
    protected function blockedDeviceResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'device_blocked',
                'message' => 'This device has been blocked. Please contact support.',
            ], 403);
        }

        auth()->logout();
        $request->session()->invalidate();

        return redirect()->route('login')
            ->with('error', 'This device has been blocked. Please contact support.');
    }

    /**
     * Return rate limited response.
     */
    protected function rateLimitedResponse(Request $request, string $action): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'rate_limited',
                'message' => 'Too many requests. Please try again later.',
                'action' => $action,
            ], 429);
        }

        return back()->with('error', 'Too many requests. Please try again later.');
    }

    /**
     * Return verification required response.
     */
    protected function verificationRequiredResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'verification_required',
                'message' => 'Additional verification is required for this action.',
                'redirect' => route('verification.required'),
            ], 403);
        }

        return redirect()->route('verification.required')
            ->with('warning', 'Additional verification is required to continue.');
    }
}
