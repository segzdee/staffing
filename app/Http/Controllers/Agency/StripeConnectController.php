<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\AgencyProfile;
use App\Services\StripeConnectService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * StripeConnectController
 *
 * Handles Stripe Connect onboarding and management for agencies.
 *
 * TASK: AGY-003 - Stripe Connect Integration for Agency Payouts
 *
 * Routes:
 * - GET /agency/stripe/connect - Initiate Connect onboarding
 * - GET /agency/stripe/callback - Handle return from Stripe
 * - GET /agency/stripe/status - Check onboarding status
 * - GET /agency/stripe/dashboard - Link to Stripe Express dashboard
 * - GET /agency/stripe/onboarding - Show onboarding page
 */
class StripeConnectController extends Controller
{
    protected StripeConnectService $stripeConnect;

    public function __construct(StripeConnectService $stripeConnect)
    {
        $this->stripeConnect = $stripeConnect;
    }

    /**
     * Show the Stripe Connect onboarding page.
     *
     * GET /agency/stripe/onboarding
     */
    public function onboarding(): View|RedirectResponse
    {
        $user = auth()->user();
        $agency = AgencyProfile::where('user_id', $user->id)->first();

        if (!$agency) {
            return redirect()->route('agency.profile')
                ->with('error', 'Please complete your agency profile first.');
        }

        // Get current status
        $statusResult = $this->stripeConnect->verifyAccountStatus($agency);

        return view('agency.stripe.onboarding', [
            'agency' => $agency,
            'stripeStatus' => $statusResult['status'] ?? 'not_created',
            'statusDetails' => $statusResult['details'] ?? [],
            'pendingCommission' => $agency->pending_commission,
        ]);
    }

    /**
     * Initiate Stripe Connect onboarding.
     *
     * GET /agency/stripe/connect
     */
    public function connect(): RedirectResponse
    {
        $user = auth()->user();
        $agency = AgencyProfile::where('user_id', $user->id)->first();

        if (!$agency) {
            return redirect()->route('agency.profile')
                ->with('error', 'Please complete your agency profile first.');
        }

        // If already fully onboarded, redirect to dashboard
        if ($agency->canReceivePayouts()) {
            return redirect()->route('agency.stripe.dashboard');
        }

        // Generate onboarding link
        $result = $this->stripeConnect->onboardAccount($agency);

        if (!$result['success']) {
            Log::error('Failed to initiate Stripe Connect onboarding', [
                'agency_id' => $agency->id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);

            return redirect()->route('agency.stripe.onboarding')
                ->with('error', $result['error'] ?? 'Failed to initiate Stripe Connect onboarding. Please try again.');
        }

        // Redirect to Stripe onboarding
        return redirect()->away($result['url']);
    }

    /**
     * Handle callback from Stripe after onboarding.
     *
     * GET /agency/stripe/callback
     */
    public function callback(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $agency = AgencyProfile::where('user_id', $user->id)->first();

        if (!$agency) {
            return redirect()->route('dashboard')
                ->with('error', 'Agency profile not found.');
        }

        // Verify account status
        $result = $this->stripeConnect->verifyAccountStatus($agency);

        if (!$result['success']) {
            return redirect()->route('agency.stripe.onboarding')
                ->with('error', 'Failed to verify Stripe account status.');
        }

        $status = $result['status'] ?? 'unknown';

        // Handle different statuses
        switch ($status) {
            case 'active':
                return redirect()->route('agency.stripe.status')
                    ->with('success', 'Stripe Connect setup completed! You can now receive commission payouts.');

            case 'pending_verification':
                return redirect()->route('agency.stripe.status')
                    ->with('warning', 'Your account is pending verification. Stripe will notify you when verification is complete.');

            case 'pending_details':
                return redirect()->route('agency.stripe.onboarding')
                    ->with('warning', 'Please complete all required information to finish setting up your Stripe account.');

            case 'restricted':
                return redirect()->route('agency.stripe.status')
                    ->with('warning', 'Your Stripe account has restrictions. Please review the requirements below.');

            default:
                return redirect()->route('agency.stripe.onboarding')
                    ->with('info', 'Please continue setting up your Stripe account.');
        }
    }

    /**
     * Show the Stripe Connect status page.
     *
     * GET /agency/stripe/status
     */
    public function status(): View|RedirectResponse
    {
        $user = auth()->user();
        $agency = AgencyProfile::where('user_id', $user->id)->first();

        if (!$agency) {
            return redirect()->route('agency.profile')
                ->with('error', 'Please complete your agency profile first.');
        }

        // Verify current status
        $result = $this->stripeConnect->verifyAccountStatus($agency);
        $agency->refresh();

        // Get balance if account is active
        $balance = null;
        if ($agency->canReceivePayouts()) {
            $balanceResult = $this->stripeConnect->retrieveBalance($agency);
            if ($balanceResult['success']) {
                $balance = $balanceResult['balance'];
            }
        }

        return view('agency.stripe.status', [
            'agency' => $agency,
            'stripeStatus' => $result['status'] ?? 'unknown',
            'statusDetails' => $result['details'] ?? [],
            'balance' => $balance,
            'pendingCommission' => $agency->pending_commission,
            'totalPayouts' => $agency->total_payouts_amount,
            'payoutCount' => $agency->total_payouts_count,
            'lastPayout' => $agency->last_payout_at,
        ]);
    }

    /**
     * Redirect to Stripe Express Dashboard.
     *
     * GET /agency/stripe/dashboard
     */
    public function dashboard(): RedirectResponse
    {
        $user = auth()->user();
        $agency = AgencyProfile::where('user_id', $user->id)->first();

        if (!$agency) {
            return redirect()->route('agency.profile')
                ->with('error', 'Please complete your agency profile first.');
        }

        if (!$agency->hasStripeConnectAccount()) {
            return redirect()->route('agency.stripe.onboarding')
                ->with('info', 'Please set up Stripe Connect first.');
        }

        // Generate dashboard link
        $result = $this->stripeConnect->createDashboardLink($agency);

        if (!$result['success']) {
            return redirect()->route('agency.stripe.status')
                ->with('error', $result['error'] ?? 'Failed to access Stripe dashboard. Please try again.');
        }

        return redirect()->away($result['url']);
    }

    /**
     * Refresh the Stripe account status (AJAX endpoint).
     *
     * POST /agency/stripe/refresh-status
     */
    public function refreshStatus(Request $request)
    {
        $user = auth()->user();
        $agency = AgencyProfile::where('user_id', $user->id)->first();

        if (!$agency) {
            return response()->json([
                'success' => false,
                'error' => 'Agency profile not found.',
            ], 404);
        }

        $result = $this->stripeConnect->verifyAccountStatus($agency);
        $agency->refresh();

        return response()->json([
            'success' => $result['success'],
            'status' => $result['status'] ?? 'unknown',
            'status_label' => $agency->stripe_status_label,
            'status_class' => $agency->stripe_status_class,
            'details' => $result['details'] ?? [],
            'can_receive_payouts' => $agency->canReceivePayouts(),
        ]);
    }

    /**
     * Get the Stripe Connect balance (AJAX endpoint).
     *
     * GET /agency/stripe/balance
     */
    public function balance(Request $request)
    {
        $user = auth()->user();
        $agency = AgencyProfile::where('user_id', $user->id)->first();

        if (!$agency) {
            return response()->json([
                'success' => false,
                'error' => 'Agency profile not found.',
            ], 404);
        }

        if (!$agency->canReceivePayouts()) {
            return response()->json([
                'success' => false,
                'error' => 'Agency cannot receive payouts. Please complete Stripe Connect onboarding.',
            ], 403);
        }

        $result = $this->stripeConnect->retrieveBalance($agency);

        return response()->json($result);
    }
}
