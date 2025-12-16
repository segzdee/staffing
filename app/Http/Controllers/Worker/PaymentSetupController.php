<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Services\WorkerPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * STAFF-REG-008: Worker Payment Setup Controller
 *
 * Handles Stripe Connect setup for worker payouts via API.
 */
class PaymentSetupController extends Controller
{
    protected WorkerPaymentService $paymentService;

    public function __construct(WorkerPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        $this->middleware('auth');
    }

    /**
     * Show payment setup page (web route).
     *
     * GET /worker/payment-setup
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $worker = auth()->user();
        $status = $this->paymentService->getPaymentStatus($worker);
        return view('worker.payment-setup', compact('status'));
    }

    /**
     * Get the worker's payment status.
     *
     * GET /api/worker/payment/status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatus(Request $request): JsonResponse
    {
        $worker = $request->user();

        if (!$worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access payment settings.',
            ], 403);
        }

        $status = $this->paymentService->getPaymentStatus($worker);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Initiate Stripe Connect onboarding.
     *
     * POST /api/worker/payment/initiate
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initiateOnboarding(Request $request): JsonResponse
    {
        $worker = $request->user();

        if (!$worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can set up payment accounts.',
            ], 403);
        }

        // Custom URLs can be passed for different frontend flows
        $refreshUrl = $request->input('refresh_url');
        $returnUrl = $request->input('return_url');

        $result = $this->paymentService->generateOnboardingLink($worker, $refreshUrl, $returnUrl);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Onboarding link generated.',
            'data' => [
                'url' => $result['url'],
                'expires_at' => $result['expires_at'],
            ],
        ]);
    }

    /**
     * Handle Stripe Connect callback.
     *
     * GET /api/worker/payment/callback
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function handleCallback(Request $request)
    {
        $worker = $request->user();

        if (!$worker || !$worker->isWorker()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }
            return redirect()->route('login');
        }

        // Refresh the account status from Stripe
        $result = $this->paymentService->verifyPayoutEnabled($worker);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment setup status refreshed.',
                'data' => $result,
            ]);
        }

        // For web requests, redirect to appropriate page
        if ($result['success'] && $result['payouts_enabled']) {
            return redirect()->route('worker.payment.success')
                ->with('success', 'Payment setup completed successfully!');
        }

        return redirect()->route('worker.payment.setup')
            ->with('info', 'Please complete the remaining steps to enable payouts.');
    }

    /**
     * Refresh payment status from Stripe.
     *
     * POST /api/worker/payment/refresh
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshStatus(Request $request): JsonResponse
    {
        $worker = $request->user();

        if (!$worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access payment settings.',
            ], 403);
        }

        $result = $this->paymentService->verifyPayoutEnabled($worker);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to refresh status.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment status refreshed.',
            'data' => $result,
        ]);
    }

    /**
     * Get missing requirements for payout capability.
     *
     * GET /api/worker/payment/requirements
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRequirements(Request $request): JsonResponse
    {
        $worker = $request->user();

        if (!$worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access payment settings.',
            ], 403);
        }

        $result = $this->paymentService->getMissingRequirements($worker);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Update payout schedule.
     *
     * PUT /api/worker/payment/schedule
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'schedule' => 'required|string|in:daily,weekly,monthly',
            'day' => 'nullable|string',
        ]);

        $worker = $request->user();

        if (!$worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can update payment settings.',
            ], 403);
        }

        $result = $this->paymentService->updatePayoutSchedule(
            $worker,
            $request->schedule,
            $request->day
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payout schedule updated.',
            'data' => $result,
        ]);
    }

    /**
     * Get a link to the Stripe Express dashboard.
     *
     * GET /api/worker/payment/dashboard-link
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboardLink(Request $request): JsonResponse
    {
        $worker = $request->user();

        if (!$worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access payment dashboard.',
            ], 403);
        }

        $result = $this->paymentService->createDashboardLink($worker);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $result['url'],
            ],
        ]);
    }

    /**
     * Handle Stripe Connect webhook.
     *
     * POST /api/webhooks/stripe-connect-worker
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret_connect_worker');

        if (!$secret) {
            return response()->json(['error' => 'Webhook secret not configured.'], 500);
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature.'], 400);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload.'], 400);
        }

        // Only process account-related events
        if (!str_starts_with($event->type, 'account.')) {
            return response()->json(['received' => true]);
        }

        $result = $this->paymentService->handleWebhook($event);

        return response()->json($result);
    }
}
