<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * FIN-011: Admin Subscription Management Controller
 *
 * Provides administrative functions for subscription management:
 * - Plan CRUD operations
 * - Subscription overview and management
 * - Complimentary subscription grants
 * - Revenue reporting and metrics
 */
class SubscriptionManagementController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Display subscription dashboard with metrics.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $metrics = $this->subscriptionService->getSubscriptionMetrics();

        // Recent subscriptions
        $recentSubscriptions = Subscription::with(['user', 'plan'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Expiring soon subscriptions
        $expiringSoon = Subscription::with(['user', 'plan'])
            ->expiringSoon(7)
            ->orderBy('current_period_end')
            ->limit(10)
            ->get();

        return view('admin.subscriptions.index', [
            'metrics' => $metrics,
            'recentSubscriptions' => $recentSubscriptions,
            'expiringSoon' => $expiringSoon,
        ]);
    }

    /**
     * Display all subscription plans.
     *
     * @return \Illuminate\View\View
     */
    public function plans(Request $request)
    {
        $query = SubscriptionPlan::query()->ordered();

        // Filter by type
        if ($request->filled('type')) {
            $query->forType($request->input('type'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $plans = $query->withCount('subscriptions')->paginate(20);

        return view('admin.subscriptions.plans', [
            'plans' => $plans,
            'types' => [
                SubscriptionPlan::TYPE_WORKER => 'Worker',
                SubscriptionPlan::TYPE_BUSINESS => 'Business',
                SubscriptionPlan::TYPE_AGENCY => 'Agency',
            ],
        ]);
    }

    /**
     * Show create plan form.
     *
     * @return \Illuminate\View\View
     */
    public function createPlan()
    {
        return view('admin.subscriptions.create-plan', [
            'types' => [
                SubscriptionPlan::TYPE_WORKER => 'Worker',
                SubscriptionPlan::TYPE_BUSINESS => 'Business',
                SubscriptionPlan::TYPE_AGENCY => 'Agency',
            ],
            'intervals' => [
                SubscriptionPlan::INTERVAL_MONTHLY => 'Monthly',
                SubscriptionPlan::INTERVAL_QUARTERLY => 'Quarterly',
                SubscriptionPlan::INTERVAL_YEARLY => 'Yearly',
            ],
        ]);
    }

    /**
     * Store a new subscription plan.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:worker,business,agency',
            'interval' => 'required|in:monthly,quarterly,yearly',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'description' => 'nullable|string|max:1000',
            'features' => 'required|array|min:1',
            'features.*' => 'string|max:100',
            'trial_days' => 'nullable|integer|min:0|max:365',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'max_users' => 'nullable|integer|min:1',
            'max_shifts_per_month' => 'nullable|integer|min:1',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['features'] = array_filter($validated['features']);
        $validated['slug'] = Str::slug($validated['name'].'-'.$validated['interval']);
        $validated['trial_days'] = $validated['trial_days'] ?? 0;
        $validated['is_popular'] = $request->boolean('is_popular');
        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            // Create Stripe product and price if configured
            if (config('services.stripe.secret')) {
                $stripeResult = $this->createStripeProductAndPrice($validated);
                if ($stripeResult) {
                    $validated['stripe_product_id'] = $stripeResult['product_id'];
                    $validated['stripe_price_id'] = $stripeResult['price_id'];
                }
            }

            $plan = SubscriptionPlan::create($validated);

            Log::info('Subscription plan created', [
                'plan_id' => $plan->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.subscriptions.plans')
                ->with('success', 'Subscription plan created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create subscription plan', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to create plan: '.$e->getMessage());
        }
    }

    /**
     * Show edit plan form.
     *
     * @return \Illuminate\View\View
     */
    public function editPlan(SubscriptionPlan $plan)
    {
        return view('admin.subscriptions.edit-plan', [
            'plan' => $plan,
            'types' => [
                SubscriptionPlan::TYPE_WORKER => 'Worker',
                SubscriptionPlan::TYPE_BUSINESS => 'Business',
                SubscriptionPlan::TYPE_AGENCY => 'Agency',
            ],
            'intervals' => [
                SubscriptionPlan::INTERVAL_MONTHLY => 'Monthly',
                SubscriptionPlan::INTERVAL_QUARTERLY => 'Quarterly',
                SubscriptionPlan::INTERVAL_YEARLY => 'Yearly',
            ],
        ]);
    }

    /**
     * Update a subscription plan.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePlan(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'features' => 'required|array|min:1',
            'features.*' => 'string|max:100',
            'trial_days' => 'nullable|integer|min:0|max:365',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'max_users' => 'nullable|integer|min:1',
            'max_shifts_per_month' => 'nullable|integer|min:1',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['features'] = array_filter($validated['features']);
        $validated['is_popular'] = $request->boolean('is_popular');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['trial_days'] = $validated['trial_days'] ?? 0;

        // Note: Price and interval cannot be changed after creation
        // to maintain consistency with Stripe and existing subscribers

        $plan->update($validated);

        Log::info('Subscription plan updated', [
            'plan_id' => $plan->id,
            'admin_id' => auth()->id(),
        ]);

        return redirect()->route('admin.subscriptions.plans')
            ->with('success', 'Subscription plan updated successfully.');
    }

    /**
     * Delete a subscription plan.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deletePlan(SubscriptionPlan $plan)
    {
        // Check if plan has active subscriptions
        if ($plan->subscriptions()->whereIn('status', ['active', 'trialing', 'past_due'])->exists()) {
            return back()->with('error', 'Cannot delete a plan with active subscriptions. Deactivate it instead.');
        }

        try {
            // Archive in Stripe if exists
            if ($plan->stripe_product_id && config('services.stripe.secret')) {
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                $stripe->products->update($plan->stripe_product_id, ['active' => false]);
            }

            $plan->delete();

            Log::info('Subscription plan deleted', [
                'plan_id' => $plan->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.subscriptions.plans')
                ->with('success', 'Subscription plan deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete subscription plan', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete plan: '.$e->getMessage());
        }
    }

    /**
     * Display all subscriptions.
     *
     * @return \Illuminate\View\View
     */
    public function subscriptions(Request $request)
    {
        $query = Subscription::with(['user', 'plan'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by plan type
        if ($request->filled('type')) {
            $query->forPlanType($request->input('type'));
        }

        // Search by user
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->paginate(25)->withQueryString();

        return view('admin.subscriptions.list', [
            'subscriptions' => $subscriptions,
            'statuses' => [
                Subscription::STATUS_ACTIVE => 'Active',
                Subscription::STATUS_TRIALING => 'Trialing',
                Subscription::STATUS_PAST_DUE => 'Past Due',
                Subscription::STATUS_CANCELED => 'Canceled',
                Subscription::STATUS_PAUSED => 'Paused',
            ],
            'types' => [
                SubscriptionPlan::TYPE_WORKER => 'Worker',
                SubscriptionPlan::TYPE_BUSINESS => 'Business',
                SubscriptionPlan::TYPE_AGENCY => 'Agency',
            ],
        ]);
    }

    /**
     * View subscription details.
     *
     * @return \Illuminate\View\View
     */
    public function viewSubscription(Subscription $subscription)
    {
        $subscription->load(['user', 'plan', 'invoices' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit(20);
        }]);

        return view('admin.subscriptions.view', [
            'subscription' => $subscription,
        ]);
    }

    /**
     * Cancel a subscription (admin action).
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancelSubscription(Request $request, Subscription $subscription)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'immediately' => 'boolean',
        ]);

        $result = $this->subscriptionService->cancelSubscription(
            $subscription,
            $request->boolean('immediately', false),
            'Admin: '.$request->input('reason')
        );

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        Log::info('Subscription canceled by admin', [
            'subscription_id' => $subscription->id,
            'admin_id' => auth()->id(),
            'reason' => $request->input('reason'),
        ]);

        return back()->with('success', 'Subscription canceled successfully.');
    }

    /**
     * Show grant complimentary subscription form.
     *
     * @return \Illuminate\View\View
     */
    public function grantForm(Request $request)
    {
        $user = null;
        if ($request->filled('user_id')) {
            $user = User::find($request->input('user_id'));
        }

        $plans = SubscriptionPlan::active()->ordered()->get();

        return view('admin.subscriptions.grant', [
            'user' => $user,
            'plans' => $plans,
        ]);
    }

    /**
     * Grant complimentary subscription.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function grant(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:subscription_plans,id',
            'duration_days' => 'required|integer|min:1|max:365',
            'reason' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        // Validate plan matches user type
        if ($plan->type !== $user->user_type) {
            return back()->withInput()
                ->with('error', 'The selected plan is not available for this user type.');
        }

        $result = $this->subscriptionService->grantComplimentarySubscription(
            $user,
            $plan,
            $validated['duration_days'],
            $validated['reason']
        );

        if (! $result['success']) {
            return back()->withInput()->with('error', $result['error']);
        }

        Log::info('Complimentary subscription granted by admin', [
            'subscription_id' => $result['subscription']->id,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'admin_id' => auth()->id(),
        ]);

        return redirect()->route('admin.subscriptions.view', $result['subscription'])
            ->with('success', 'Complimentary subscription granted successfully.');
    }

    /**
     * Display revenue reporting.
     *
     * @return \Illuminate\View\View
     */
    public function revenue(Request $request)
    {
        $startDate = $request->input('start_date', now()->subMonths(6)->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        // Revenue by month
        $monthlyRevenue = SubscriptionInvoice::query()
            ->selectRaw('DATE_FORMAT(paid_at, "%Y-%m") as month, SUM(total) as revenue, COUNT(*) as count')
            ->where('status', SubscriptionInvoice::STATUS_PAID)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Revenue by plan
        $revenueByPlan = SubscriptionInvoice::query()
            ->join('subscriptions', 'subscription_invoices.subscription_id', '=', 'subscriptions.id')
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->selectRaw('subscription_plans.name, subscription_plans.type, SUM(subscription_invoices.total) as revenue, COUNT(*) as count')
            ->where('subscription_invoices.status', SubscriptionInvoice::STATUS_PAID)
            ->whereBetween('subscription_invoices.paid_at', [$startDate, $endDate])
            ->groupBy('subscription_plans.id', 'subscription_plans.name', 'subscription_plans.type')
            ->orderByDesc('revenue')
            ->get();

        // Total revenue
        $totalRevenue = SubscriptionInvoice::query()
            ->where('status', SubscriptionInvoice::STATUS_PAID)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('total');

        // Current MRR
        $metrics = $this->subscriptionService->getSubscriptionMetrics();

        return view('admin.subscriptions.revenue', [
            'monthlyRevenue' => $monthlyRevenue,
            'revenueByPlan' => $revenueByPlan,
            'totalRevenue' => $totalRevenue,
            'metrics' => $metrics,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Export subscriptions to CSV.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $query = Subscription::with(['user', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $subscriptions = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="subscriptions-'.now()->format('Y-m-d').'.csv"',
        ];

        $callback = function () use ($subscriptions) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'ID',
                'User ID',
                'User Name',
                'User Email',
                'Plan',
                'Type',
                'Status',
                'Price',
                'Trial Ends',
                'Period Start',
                'Period End',
                'Created At',
            ]);

            // Data rows
            foreach ($subscriptions as $sub) {
                fputcsv($handle, [
                    $sub->id,
                    $sub->user_id,
                    $sub->user?->name,
                    $sub->user?->email,
                    $sub->plan?->name,
                    $sub->plan?->type,
                    $sub->status,
                    $sub->plan?->price,
                    $sub->trial_ends_at?->toDateString(),
                    $sub->current_period_start?->toDateString(),
                    $sub->current_period_end?->toDateString(),
                    $sub->created_at->toDateString(),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Create Stripe product and price for a plan.
     */
    protected function createStripeProductAndPrice(array $planData): ?array
    {
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

            // Create product
            $product = $stripe->products->create([
                'name' => $planData['name'],
                'description' => $planData['description'] ?? null,
                'metadata' => [
                    'type' => $planData['type'],
                    'platform' => 'overtimestaff',
                ],
            ]);

            // Map interval to Stripe format
            $stripeInterval = match ($planData['interval']) {
                'monthly' => 'month',
                'quarterly' => 'month',
                'yearly' => 'year',
                default => 'month',
            };

            $intervalCount = match ($planData['interval']) {
                'quarterly' => 3,
                default => 1,
            };

            // Create price
            $price = $stripe->prices->create([
                'product' => $product->id,
                'unit_amount' => (int) round($planData['price'] * 100),
                'currency' => strtolower($planData['currency']),
                'recurring' => [
                    'interval' => $stripeInterval,
                    'interval_count' => $intervalCount,
                ],
                'metadata' => [
                    'plan_type' => $planData['type'],
                ],
            ]);

            return [
                'product_id' => $product->id,
                'price_id' => $price->id,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create Stripe product/price', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
