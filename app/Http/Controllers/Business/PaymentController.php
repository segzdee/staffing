<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\BusinessPaymentService;
use App\Http\Requests\Business\AddPaymentMethodRequest;
use App\Http\Requests\Business\VerifyMicroDepositsRequest;
use App\Models\BusinessPaymentMethod;
use App\Notifications\PaymentMethodAddedNotification;
use App\Notifications\PaymentMethodVerifiedNotification;
use App\Notifications\PaymentVerificationRequiredNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PaymentController
 *
 * BIZ-REG-007: Business Payment Setup
 *
 * Handles all business payment method operations:
 * - Payment setup wizard
 * - Adding card and bank payment methods
 * - Verification (card auth, micro-deposits)
 * - Managing default payment method
 * - Removing payment methods
 */
class PaymentController extends Controller
{
    protected BusinessPaymentService $paymentService;

    public function __construct(BusinessPaymentService $paymentService)
    {
        $this->middleware(['auth', 'business']);
        $this->paymentService = $paymentService;
    }

    /**
     * Show payment setup page.
     *
     * GET /business/payment/setup
     */
    public function setupPayment()
    {
        $user = Auth::user();
        $business = $user->businessProfile;

        if (!$business) {
            return redirect()->route('business.profile.complete')
                ->with('error', 'Please complete your business profile first.');
        }

        // Get existing payment methods
        $methodsResult = $this->paymentService->getPaymentMethods($business);

        // Create setup intent for Stripe Elements
        $setupIntentResult = $this->paymentService->createSetupIntent($business, 'all');

        return view('business.payment.setup', [
            'user' => $user,
            'business' => $business,
            'paymentMethods' => $methodsResult['payment_methods'],
            'hasUsableMethod' => $methodsResult['has_usable_method'],
            'defaultId' => $methodsResult['default_id'],
            'setupIntent' => $setupIntentResult['success'] ? $setupIntentResult : null,
            'stripeKey' => config('services.stripe.key'),
        ]);
    }

    /**
     * Create setup intent for adding payment method.
     *
     * POST /business/payment/setup-intent
     */
    public function createSetupIntent(Request $request)
    {
        $request->validate([
            'type' => 'sometimes|string|in:card,us_bank_account,sepa_debit,bacs_debit,all',
        ]);

        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $type = $request->input('type', 'card');
        $result = $this->paymentService->createSetupIntent($business, $type);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Add a new payment method.
     *
     * POST /business/payment/methods
     */
    public function addPaymentMethod(AddPaymentMethodRequest $request)
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $result = $this->paymentService->addPaymentMethod(
            $business,
            $request->input('setup_intent_id'),
            $request->input('billing_details', [])
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        // Send notification
        $user = Auth::user();
        try {
            // $user->notify(new PaymentMethodAddedNotification($result['payment_method']));

            if ($result['needs_verification']) {
                // $user->notify(new PaymentVerificationRequiredNotification($result['payment_method']));
            }
        } catch (\Exception $e) {
            // Log but don't fail the request
            \Log::warning('Failed to send payment method notification', ['error' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->route('business.payment.setup')
            ->with('success', 'Payment method added successfully.');
    }

    /**
     * Get all payment methods for business.
     *
     * GET /business/payment/methods
     */
    public function getPaymentMethods()
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $result = $this->paymentService->getPaymentMethods($business);

        return response()->json($result);
    }

    /**
     * Set a payment method as default.
     *
     * PUT /business/payment/methods/{id}/default
     */
    public function setDefaultPaymentMethod(int $id)
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $result = $this->paymentService->setDefaultPaymentMethod($business, $id);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Remove a payment method.
     *
     * DELETE /business/payment/methods/{id}
     */
    public function removePaymentMethod(int $id)
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $result = $this->paymentService->removePaymentMethod($business, $id);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Verify a card payment method ($1 auth).
     *
     * POST /business/payment/verify/card
     */
    public function verifyCard(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|integer',
        ]);

        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $result = $this->paymentService->verifyCard($business, $request->input('payment_method_id'));

        if ($result['success']) {
            // Send verification complete notification
            try {
                // Auth::user()->notify(new PaymentMethodVerifiedNotification($result['payment_method']));
            } catch (\Exception $e) {
                \Log::warning('Failed to send verification notification', ['error' => $e->getMessage()]);
            }
        }

        if (!$result['success'] && !($result['requires_action'] ?? false)) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Initiate micro-deposit verification for bank account.
     *
     * POST /business/payment/verify/bank/initiate
     */
    public function initiateMicroDepositVerification(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|integer',
        ]);

        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $result = $this->paymentService->initiateMicroDepositVerification(
            $business,
            $request->input('payment_method_id')
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Verify bank account with micro-deposit amounts.
     *
     * POST /business/payment/verify/bank/amounts
     */
    public function verifyMicroDeposits(VerifyMicroDepositsRequest $request)
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $result = $this->paymentService->verifyMicroDeposits(
            $business,
            $request->input('payment_method_id'),
            $request->input('amount_1'),
            $request->input('amount_2')
        );

        if ($result['success']) {
            try {
                // Auth::user()->notify(new PaymentMethodVerifiedNotification($result['payment_method']));
            } catch (\Exception $e) {
                \Log::warning('Failed to send verification notification', ['error' => $e->getMessage()]);
            }
        }

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Complete payment setup and mark as ready.
     *
     * POST /business/payment/complete
     */
    public function completeSetup()
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $result = $this->paymentService->completePaymentSetup($business);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Get payment setup status.
     *
     * GET /business/payment/status
     */
    public function getStatus()
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $methodsResult = $this->paymentService->getPaymentMethods($business);

        return response()->json([
            'success' => true,
            'payment_setup_complete' => $business->payment_setup_complete,
            'has_stripe_customer' => !empty($business->stripe_customer_id),
            'has_usable_method' => $methodsResult['has_usable_method'],
            'default_method_id' => $methodsResult['default_id'],
            'methods_count' => count($methodsResult['payment_methods']),
            'can_post_shifts' => $this->paymentService->canBusinessPostShifts($business),
        ]);
    }

    /**
     * Update billing address for a payment method.
     *
     * PUT /business/payment/methods/{id}/billing
     */
    public function updateBillingAddress(Request $request, int $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|string|max:50',
            'line1' => 'required|string|max:255',
            'line2' => 'sometimes|nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'sometimes|nullable|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|size:2',
        ]);

        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $paymentMethod = BusinessPaymentMethod::where('business_profile_id', $business->id)
            ->where('id', $id)
            ->first();

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'error' => 'Payment method not found.',
            ], 404);
        }

        $paymentMethod->updateBillingAddress($request->only([
            'name', 'email', 'phone', 'line1', 'line2', 'city', 'state', 'postal_code', 'country'
        ]));

        return response()->json([
            'success' => true,
            'payment_method' => $paymentMethod->fresh(),
        ]);
    }

    /**
     * Set nickname for a payment method.
     *
     * PUT /business/payment/methods/{id}/nickname
     */
    public function setNickname(Request $request, int $id)
    {
        $request->validate([
            'nickname' => 'required|string|max:100',
        ]);

        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $paymentMethod = BusinessPaymentMethod::where('business_profile_id', $business->id)
            ->where('id', $id)
            ->first();

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'error' => 'Payment method not found.',
            ], 404);
        }

        $paymentMethod->update(['nickname' => $request->input('nickname')]);

        return response()->json([
            'success' => true,
            'payment_method' => $paymentMethod->fresh(),
        ]);
    }
}
