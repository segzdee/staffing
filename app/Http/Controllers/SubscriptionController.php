<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * FIN-011: Subscription Controller
 *
 * Handles user-facing subscription management including:
 * - Viewing available plans
 * - Subscribing to a plan
 * - Managing active subscription
 * - Viewing invoices
 * - Updating payment methods
 */
class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display available subscription plans.
     *
     * @return \Illuminate\View\View
     */
    public function plans(Request $request)
    {
        $user = $request->user();
        $interval = $request->input('interval', 'monthly');

        $plans = $this->subscriptionService->getAvailablePlans($user, $interval);
        $currentSubscription = $this->subscriptionService->getActiveSubscription($user);

        // Get all intervals for tabs
        $allPlans = $this->subscriptionService->getAvailablePlans($user);
        $availableIntervals = $allPlans->pluck('interval')->unique()->values();

        return view('subscription.plans', [
            'plans' => $plans,
            'currentSubscription' => $currentSubscription,
            'selectedInterval' => $interval,
            'availableIntervals' => $availableIntervals,
            'userType' => $user->user_type,
        ]);
    }

    /**
     * Show subscription checkout page.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function checkout(Request $request, SubscriptionPlan $plan)
    {
        $user = $request->user();

        // Validate plan is for user's type
        if ($plan->type !== $user->user_type) {
            return redirect()->route('subscription.plans')
                ->with('error', 'This plan is not available for your account type.');
        }

        // Check for existing subscription
        $currentSubscription = $this->subscriptionService->getActiveSubscription($user);
        if ($currentSubscription) {
            return redirect()->route('subscription.manage')
                ->with('info', 'You already have an active subscription. You can upgrade or change your plan from here.');
        }

        $intent = null;
        if ($user->stripe_id) {
            try {
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                $intent = $stripe->setupIntents->create([
                    'customer' => $user->stripe_id,
                    'payment_method_types' => ['card'],
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create setup intent', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('subscription.checkout', [
            'plan' => $plan,
            'user' => $user,
            'intent' => $intent,
            'stripeKey' => config('services.stripe.key'),
        ]);
    }

    /**
     * Process subscription creation.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribe(Request $request, SubscriptionPlan $plan)
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        $user = $request->user();

        $result = $this->subscriptionService->createSubscription(
            $user,
            $plan,
            $request->input('payment_method')
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        }

        $response = [
            'success' => true,
            'message' => 'Subscription created successfully!',
            'redirect' => route('subscription.manage'),
        ];

        // If additional payment is required (3D Secure)
        if (isset($result['client_secret']) && $result['requires_payment']) {
            $response['requires_action'] = true;
            $response['client_secret'] = $result['client_secret'];
        }

        return response()->json($response);
    }

    /**
     * Display subscription management page.
     *
     * @return \Illuminate\View\View
     */
    public function manage(Request $request)
    {
        $user = $request->user();
        $subscription = $this->subscriptionService->getActiveSubscription($user);
        $invoices = $this->subscriptionService->getUserInvoices($user, 10);

        // Get available plans for upgrade/downgrade
        $availablePlans = $this->subscriptionService->getAvailablePlans($user);

        return view('subscription.manage', [
            'subscription' => $subscription,
            'invoices' => $invoices,
            'availablePlans' => $availablePlans,
            'user' => $user,
        ]);
    }

    /**
     * Cancel subscription.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
            'immediately' => 'boolean',
        ]);

        $user = $request->user();
        $subscription = $this->subscriptionService->getActiveSubscription($user);

        if (! $subscription) {
            return redirect()->route('subscription.plans')
                ->with('error', 'No active subscription found.');
        }

        $result = $this->subscriptionService->cancelSubscription(
            $subscription,
            $request->boolean('immediately', false),
            $request->input('reason')
        );

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        $message = $request->boolean('immediately')
            ? 'Your subscription has been canceled immediately.'
            : 'Your subscription will be canceled at the end of the current billing period.';

        return redirect()->route('subscription.manage')
            ->with('success', $message);
    }

    /**
     * Resume a canceled subscription.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resume(Request $request)
    {
        $user = $request->user();
        $subscription = Subscription::where('user_id', $user->id)
            ->where('cancel_at_period_end', true)
            ->first();

        if (! $subscription) {
            return redirect()->route('subscription.manage')
                ->with('error', 'No subscription found to resume.');
        }

        $result = $this->subscriptionService->resumeSubscription($subscription);

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        return redirect()->route('subscription.manage')
            ->with('success', 'Your subscription has been resumed.');
    }

    /**
     * Change subscription plan.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePlan(Request $request, SubscriptionPlan $plan)
    {
        $user = $request->user();
        $subscription = $this->subscriptionService->getActiveSubscription($user);

        if (! $subscription) {
            return redirect()->route('subscription.checkout', $plan)
                ->with('info', 'You need to subscribe first.');
        }

        $result = $this->subscriptionService->changePlan($subscription, $plan);

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        return redirect()->route('subscription.manage')
            ->with('success', 'Your subscription has been updated to '.$plan->name.'.');
    }

    /**
     * Display invoices list.
     *
     * @return \Illuminate\View\View
     */
    public function invoices(Request $request)
    {
        $user = $request->user();
        $invoices = $this->subscriptionService->getUserInvoices($user, 50);

        return view('subscription.invoices', [
            'invoices' => $invoices,
        ]);
    }

    /**
     * Download invoice PDF.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function downloadInvoice(Request $request, $invoiceId)
    {
        $user = $request->user();
        $invoice = \App\Models\SubscriptionInvoice::where('id', $invoiceId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($invoice->pdf_url) {
            return redirect($invoice->pdf_url);
        }

        return back()->with('error', 'Invoice PDF is not available.');
    }

    /**
     * Show payment method update form.
     *
     * @return \Illuminate\View\View
     */
    public function paymentMethod(Request $request)
    {
        $user = $request->user();
        $subscription = $this->subscriptionService->getActiveSubscription($user);

        $intent = null;
        if ($user->stripe_id) {
            try {
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                $intent = $stripe->setupIntents->create([
                    'customer' => $user->stripe_id,
                    'payment_method_types' => ['card'],
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create setup intent', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('subscription.payment-method', [
            'subscription' => $subscription,
            'intent' => $intent,
            'stripeKey' => config('services.stripe.key'),
        ]);
    }

    /**
     * Update payment method.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePaymentMethod(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        $user = $request->user();
        $subscription = $this->subscriptionService->getActiveSubscription($user);

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'error' => 'No active subscription found.',
            ], 404);
        }

        $result = $this->subscriptionService->updatePaymentMethod(
            $subscription,
            $request->input('payment_method')
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment method updated successfully.',
        ]);
    }

    /**
     * Check feature access (API endpoint).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkFeature(Request $request, string $feature)
    {
        $user = $request->user();
        $hasAccess = $this->subscriptionService->checkFeatureAccess($user, $feature);

        return response()->json([
            'feature' => $feature,
            'has_access' => $hasAccess,
        ]);
    }
}
