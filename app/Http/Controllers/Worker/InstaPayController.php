<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\InstapayRequest;
use App\Services\InstaPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FIN-004: InstaPay (Same-Day Payout) Controller
 *
 * Handles InstaPay requests for workers to receive instant/same-day payouts.
 */
class InstaPayController extends Controller
{
    public function __construct(protected InstaPayService $instaPayService)
    {
        $this->middleware('auth');
    }

    /**
     * Show InstaPay dashboard page (web route).
     *
     * GET /worker/instapay
     */
    public function index(Request $request): View
    {
        $worker = $request->user();

        $eligibility = $this->instaPayService->getEligibilityStatus($worker);
        $settings = $worker->getOrCreateInstapaySettings();
        $availableBalance = $this->instaPayService->getAvailableBalance($worker);
        $pendingEarnings = $this->instaPayService->getPendingEarnings($worker);
        $history = $this->instaPayService->getRequestHistory($worker);
        $statistics = $this->instaPayService->getUserStatistics($worker);

        return view('worker.instapay.index', compact(
            'worker',
            'eligibility',
            'settings',
            'availableBalance',
            'pendingEarnings',
            'history',
            'statistics'
        ));
    }

    /**
     * Get InstaPay status and available balance.
     *
     * GET /api/worker/instapay/status
     */
    public function getStatus(Request $request): JsonResponse
    {
        $worker = $request->user();

        if (! $worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access InstaPay.',
            ], 403);
        }

        $eligibility = $this->instaPayService->getEligibilityStatus($worker);
        $settings = $worker->getOrCreateInstapaySettings();

        return response()->json([
            'success' => true,
            'data' => [
                'eligible' => $eligibility['eligible'],
                'eligibility_reasons' => $eligibility['reasons'],
                'enabled' => $settings->isEnabled(),
                'available_balance' => $this->instaPayService->getAvailableBalance($worker),
                'daily_limit' => $this->instaPayService->getDailyLimit($worker),
                'remaining_limit' => $this->instaPayService->getRemainingDailyLimit($worker),
                'today_total' => $worker->getTodayInstapayTotal(),
                'preferred_method' => $settings->preferred_method,
                'auto_request' => $settings->auto_request,
                'minimum_amount' => $settings->getEffectiveMinimumAmount(),
                'is_processing_day' => $settings->isProcessingDay(),
                'is_before_cutoff' => $settings->isBeforeCutoff(),
                'available_methods' => $settings->getAvailableMethods(),
            ],
        ]);
    }

    /**
     * Calculate fee preview for a given amount.
     *
     * POST /api/worker/instapay/calculate
     */
    public function calculateFee(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $fees = $this->instaPayService->calculateFees($request->amount);

        return response()->json([
            'success' => true,
            'data' => [
                'gross_amount' => round($request->amount, 2),
                'instapay_fee' => $fees['instapay_fee'],
                'platform_fee' => $fees['platform_fee'],
                'net_amount' => $fees['net_amount'],
                'fee_percent' => config('instapay.fee_percent', 1.5),
            ],
        ]);
    }

    /**
     * Request instant payout.
     *
     * POST /api/worker/instapay/request
     */
    public function requestPayout(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'nullable|string|in:stripe,paypal,bank_transfer',
        ]);

        $worker = $request->user();

        if (! $worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can request InstaPay.',
            ], 403);
        }

        try {
            $instapayRequest = $this->instaPayService->requestInstaPay(
                $worker,
                $request->amount,
                $request->method
            );

            return response()->json([
                'success' => true,
                'message' => 'InstaPay request submitted successfully.',
                'data' => [
                    'request_id' => $instapayRequest->id,
                    'status' => $instapayRequest->status,
                    'gross_amount' => $instapayRequest->gross_amount,
                    'fee' => $instapayRequest->instapay_fee,
                    'net_amount' => $instapayRequest->net_amount,
                    'method' => $instapayRequest->payout_method,
                    'requested_at' => $instapayRequest->requested_at->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a pending InstaPay request.
     *
     * POST /api/worker/instapay/{request}/cancel
     */
    public function cancelRequest(Request $request, InstapayRequest $instapayRequest): JsonResponse
    {
        $worker = $request->user();

        // Ensure the request belongs to the worker
        if ($instapayRequest->user_id !== $worker->id) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found.',
            ], 404);
        }

        if (! $instapayRequest->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'This request cannot be cancelled.',
            ], 422);
        }

        try {
            $this->instaPayService->cancelRequest($instapayRequest);

            return response()->json([
                'success' => true,
                'message' => 'Request cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get InstaPay request history.
     *
     * GET /api/worker/instapay/history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $worker = $request->user();

        if (! $worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access InstaPay.',
            ], 403);
        }

        $limit = min($request->input('limit', 20), 100);
        $history = $this->instaPayService->getRequestHistory($worker, $limit);

        return response()->json([
            'success' => true,
            'data' => $history->map(fn ($req) => [
                'id' => $req->id,
                'gross_amount' => $req->gross_amount,
                'fee' => $req->instapay_fee,
                'net_amount' => $req->net_amount,
                'status' => $req->status,
                'method' => $req->payout_method,
                'reference' => $req->payout_reference,
                'requested_at' => $req->requested_at->toIso8601String(),
                'completed_at' => $req->completed_at?->toIso8601String(),
                'failure_reason' => $req->failure_reason,
            ]),
        ]);
    }

    /**
     * Get a specific InstaPay request.
     *
     * GET /api/worker/instapay/{request}
     */
    public function show(Request $request, InstapayRequest $instapayRequest): JsonResponse
    {
        $worker = $request->user();

        if ($instapayRequest->user_id !== $worker->id) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $instapayRequest->id,
                'gross_amount' => $instapayRequest->gross_amount,
                'fee' => $instapayRequest->instapay_fee,
                'net_amount' => $instapayRequest->net_amount,
                'status' => $instapayRequest->status,
                'method' => $instapayRequest->payout_method,
                'reference' => $instapayRequest->payout_reference,
                'requested_at' => $instapayRequest->requested_at->toIso8601String(),
                'processed_at' => $instapayRequest->processed_at?->toIso8601String(),
                'completed_at' => $instapayRequest->completed_at?->toIso8601String(),
                'failure_reason' => $instapayRequest->failure_reason,
                'can_cancel' => $instapayRequest->canBeCancelled(),
            ],
        ]);
    }

    /**
     * Get earnings awaiting payout.
     *
     * GET /api/worker/instapay/earnings
     */
    public function getEarnings(Request $request): JsonResponse
    {
        $worker = $request->user();

        if (! $worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access InstaPay.',
            ], 403);
        }

        $earnings = $this->instaPayService->getEarningsAwaitingPayout($worker);
        $pendingEarnings = $this->instaPayService->getPendingEarnings($worker);

        return response()->json([
            'success' => true,
            'data' => [
                'available' => $earnings->map(fn ($payment) => [
                    'id' => $payment->id,
                    'amount' => $payment->amount_net->getAmount() / 100,
                    'shift_id' => $payment->assignment?->shift_id,
                    'shift_date' => $payment->assignment?->shift?->shift_date,
                    'business_name' => $payment->business?->name,
                    'released_at' => $payment->released_at?->toIso8601String(),
                ]),
                'pending' => $pendingEarnings->map(fn ($payment) => [
                    'id' => $payment->id,
                    'amount' => $payment->amount_net->getAmount() / 100,
                    'shift_id' => $payment->assignment?->shift_id,
                    'shift_date' => $payment->assignment?->shift?->shift_date,
                    'business_name' => $payment->business?->name,
                    'escrow_held_at' => $payment->escrow_held_at?->toIso8601String(),
                ]),
                'total_available' => $this->instaPayService->getAvailableBalance($worker),
                'total_pending' => $pendingEarnings->sum(fn ($p) => $p->amount_net->getAmount() / 100),
            ],
        ]);
    }

    /**
     * Get InstaPay statistics.
     *
     * GET /api/worker/instapay/statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $worker = $request->user();

        if (! $worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access InstaPay.',
            ], 403);
        }

        $statistics = $this->instaPayService->getUserStatistics($worker);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Update InstaPay settings.
     *
     * PUT /api/worker/instapay/settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'sometimes|boolean',
            'preferred_method' => 'sometimes|string|in:stripe,paypal,bank_transfer',
            'auto_request' => 'sometimes|boolean',
            'minimum_amount' => 'sometimes|numeric|min:1',
            'daily_cutoff' => 'sometimes|date_format:H:i',
        ]);

        $worker = $request->user();

        if (! $worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can update InstaPay settings.',
            ], 403);
        }

        $settings = $worker->getOrCreateInstapaySettings();

        $updateData = [];

        if ($request->has('enabled')) {
            $updateData['enabled'] = $request->boolean('enabled');
        }

        if ($request->has('preferred_method')) {
            $updateData['preferred_method'] = $request->preferred_method;
        }

        if ($request->has('auto_request')) {
            $updateData['auto_request'] = $request->boolean('auto_request');
        }

        if ($request->has('minimum_amount')) {
            $minSystem = config('instapay.minimum_amount', 10.00);
            $updateData['minimum_amount'] = max($request->minimum_amount, $minSystem);
        }

        if ($request->has('daily_cutoff')) {
            $updateData['daily_cutoff'] = $request->daily_cutoff.':00';
        }

        $settings->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'data' => [
                'enabled' => $settings->enabled,
                'preferred_method' => $settings->preferred_method,
                'auto_request' => $settings->auto_request,
                'minimum_amount' => $settings->minimum_amount,
                'daily_cutoff' => $settings->daily_cutoff,
            ],
        ]);
    }

    /**
     * Get InstaPay settings.
     *
     * GET /api/worker/instapay/settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $worker = $request->user();

        if (! $worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access InstaPay settings.',
            ], 403);
        }

        $settings = $worker->getOrCreateInstapaySettings();

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $settings->enabled,
                'preferred_method' => $settings->preferred_method,
                'auto_request' => $settings->auto_request,
                'minimum_amount' => $settings->minimum_amount,
                'daily_cutoff' => $settings->daily_cutoff,
                'daily_limit' => $settings->getEffectiveDailyLimit(),
                'available_methods' => $settings->getAvailableMethods(),
            ],
        ]);
    }
}
