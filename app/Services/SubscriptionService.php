<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

/**
 * FIN-011: Subscription Service
 *
 * Handles all subscription-related business logic including:
 * - Plan retrieval and filtering
 * - Subscription creation, cancellation, and management
 * - Stripe integration for payment processing
 * - Webhook handling
 * - Feature access control
 * - Subscription metrics and reporting
 */
class SubscriptionService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient([
            'api_key' => config('services.stripe.secret'),
            'stripe_version' => config('services.stripe.api_version', '2023-10-16'),
        ]);
    }

    /**
     * Get available subscription plans for a user based on their type.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailablePlans(User $user, ?string $interval = null)
    {
        $query = SubscriptionPlan::query()
            ->active()
            ->forType($user->user_type)
            ->ordered();

        if ($interval) {
            $query->forInterval($interval);
        }

        return $query->get();
    }

    /**
     * Get all active plans grouped by type.
     *
     * @return array<string, \Illuminate\Database\Eloquent\Collection>
     */
    public function getAllPlansGrouped(): array
    {
        $plans = SubscriptionPlan::active()->ordered()->get();

        return [
            'worker' => $plans->where('type', SubscriptionPlan::TYPE_WORKER)->values(),
            'business' => $plans->where('type', SubscriptionPlan::TYPE_BUSINESS)->values(),
            'agency' => $plans->where('type', SubscriptionPlan::TYPE_AGENCY)->values(),
        ];
    }

    /**
     * Get a user's active subscription.
     */
    public function getActiveSubscription(User $user): ?Subscription
    {
        return Subscription::where('user_id', $user->id)
            ->valid()
            ->with('plan')
            ->first();
    }

    /**
     * Check if a user has an active subscription.
     */
    public function hasActiveSubscription(User $user): bool
    {
        return $this->getActiveSubscription($user) !== null;
    }

    /**
     * Create a new subscription for a user.
     *
     * @return array{success: bool, subscription?: Subscription, client_secret?: string, error?: string}
     */
    public function createSubscription(
        User $user,
        SubscriptionPlan $plan,
        ?string $paymentMethodId = null,
        bool $startTrial = true
    ): array {
        try {
            // Validate plan matches user type
            if ($plan->type !== $user->user_type) {
                return [
                    'success' => false,
                    'error' => 'This plan is not available for your account type.',
                ];
            }

            // Check for existing active subscription
            $existingSubscription = $this->getActiveSubscription($user);
            if ($existingSubscription) {
                return [
                    'success' => false,
                    'error' => 'You already have an active subscription. Please cancel or change your current plan.',
                    'existing_subscription' => $existingSubscription,
                ];
            }

            return DB::transaction(function () use ($user, $plan, $paymentMethodId, $startTrial) {
                // Ensure user has a Stripe customer
                $stripeCustomerId = $this->ensureStripeCustomer($user, $paymentMethodId);

                // Calculate trial end date
                $trialEnd = null;
                if ($startTrial && $plan->trial_days > 0) {
                    $trialEnd = now()->addDays($plan->trial_days)->timestamp;
                }

                // Create Stripe subscription
                $stripeSubscription = $this->stripe->subscriptions->create([
                    'customer' => $stripeCustomerId,
                    'items' => [
                        ['price' => $plan->stripe_price_id],
                    ],
                    'trial_end' => $trialEnd,
                    'payment_behavior' => 'default_incomplete',
                    'payment_settings' => [
                        'save_default_payment_method' => 'on_subscription',
                    ],
                    'expand' => ['latest_invoice.payment_intent'],
                    'metadata' => [
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'platform' => 'overtimestaff',
                    ],
                ]);

                // Create local subscription record
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'stripe_subscription_id' => $stripeSubscription->id,
                    'stripe_customer_id' => $stripeCustomerId,
                    'status' => $this->mapStripeStatus($stripeSubscription->status),
                    'trial_ends_at' => $trialEnd ? Carbon::createFromTimestamp($trialEnd) : null,
                    'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                    'current_period_end' => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                ]);

                Log::info('Subscription created', [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $stripeSubscription->id,
                ]);

                $result = [
                    'success' => true,
                    'subscription' => $subscription,
                ];

                // Include client secret if payment required
                if ($stripeSubscription->latest_invoice?->payment_intent) {
                    $result['client_secret'] = $stripeSubscription->latest_invoice->payment_intent->client_secret;
                    $result['requires_payment'] = true;
                }

                return $result;
            });
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Failed to create subscription', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        } catch (\Exception $e) {
            Log::error('Subscription creation failed', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create subscription. Please try again.',
            ];
        }
    }

    /**
     * Cancel a subscription.
     *
     * @return array{success: bool, subscription?: Subscription, error?: string}
     */
    public function cancelSubscription(
        Subscription $subscription,
        bool $immediately = false,
        ?string $reason = null
    ): array {
        try {
            if ($subscription->isCanceled()) {
                return [
                    'success' => false,
                    'error' => 'This subscription is already canceled.',
                ];
            }

            return DB::transaction(function () use ($subscription, $immediately, $reason) {
                if ($subscription->stripe_subscription_id) {
                    if ($immediately) {
                        // Cancel immediately
                        $this->stripe->subscriptions->cancel($subscription->stripe_subscription_id);
                    } else {
                        // Cancel at period end
                        $this->stripe->subscriptions->update(
                            $subscription->stripe_subscription_id,
                            ['cancel_at_period_end' => true]
                        );
                    }
                }

                if ($immediately) {
                    $subscription->markAsCanceled($reason, true);
                } else {
                    $subscription->update([
                        'cancel_at_period_end' => true,
                        'cancellation_reason' => $reason,
                        'ends_at' => $subscription->current_period_end,
                    ]);
                }

                Log::info('Subscription canceled', [
                    'subscription_id' => $subscription->id,
                    'immediately' => $immediately,
                    'reason' => $reason,
                ]);

                return [
                    'success' => true,
                    'subscription' => $subscription->fresh(),
                ];
            });
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Failed to cancel subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Resume a canceled subscription.
     *
     * @return array{success: bool, subscription?: Subscription, error?: string}
     */
    public function resumeSubscription(Subscription $subscription): array
    {
        try {
            if (! $subscription->willCancelAtPeriodEnd()) {
                return [
                    'success' => false,
                    'error' => 'This subscription is not scheduled for cancellation.',
                ];
            }

            if ($subscription->hasEnded()) {
                return [
                    'success' => false,
                    'error' => 'This subscription has already ended. Please create a new subscription.',
                ];
            }

            return DB::transaction(function () use ($subscription) {
                if ($subscription->stripe_subscription_id) {
                    $this->stripe->subscriptions->update(
                        $subscription->stripe_subscription_id,
                        ['cancel_at_period_end' => false]
                    );
                }

                $subscription->resume();

                Log::info('Subscription resumed', [
                    'subscription_id' => $subscription->id,
                ]);

                return [
                    'success' => true,
                    'subscription' => $subscription->fresh(),
                ];
            });
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Failed to resume subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Change subscription to a different plan.
     *
     * @return array{success: bool, subscription?: Subscription, error?: string}
     */
    public function changePlan(Subscription $subscription, SubscriptionPlan $newPlan): array
    {
        try {
            if (! $subscription->isValid()) {
                return [
                    'success' => false,
                    'error' => 'Cannot change plan for an invalid subscription.',
                ];
            }

            if ($newPlan->type !== $subscription->plan->type) {
                return [
                    'success' => false,
                    'error' => 'Cannot switch to a plan for a different account type.',
                ];
            }

            if ($subscription->plan->id === $newPlan->id) {
                return [
                    'success' => false,
                    'error' => 'You are already subscribed to this plan.',
                ];
            }

            return DB::transaction(function () use ($subscription, $newPlan) {
                if ($subscription->stripe_subscription_id) {
                    // Get current subscription items
                    $stripeSubscription = $this->stripe->subscriptions->retrieve(
                        $subscription->stripe_subscription_id,
                        ['expand' => ['items']]
                    );

                    // Update with new price
                    $this->stripe->subscriptions->update(
                        $subscription->stripe_subscription_id,
                        [
                            'items' => [
                                [
                                    'id' => $stripeSubscription->items->data[0]->id,
                                    'price' => $newPlan->stripe_price_id,
                                ],
                            ],
                            'proration_behavior' => 'create_prorations',
                        ]
                    );
                }

                $oldPlanId = $subscription->subscription_plan_id;
                $subscription->swapPlan($newPlan);

                Log::info('Subscription plan changed', [
                    'subscription_id' => $subscription->id,
                    'old_plan_id' => $oldPlanId,
                    'new_plan_id' => $newPlan->id,
                ]);

                return [
                    'success' => true,
                    'subscription' => $subscription->fresh(['plan']),
                ];
            });
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Failed to change subscription plan', [
                'subscription_id' => $subscription->id,
                'new_plan_id' => $newPlan->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Pause a subscription (if supported).
     *
     * @return array{success: bool, subscription?: Subscription, error?: string}
     */
    public function pauseSubscription(Subscription $subscription): array
    {
        try {
            if (! $subscription->isActive()) {
                return [
                    'success' => false,
                    'error' => 'Only active subscriptions can be paused.',
                ];
            }

            return DB::transaction(function () use ($subscription) {
                if ($subscription->stripe_subscription_id) {
                    $this->stripe->subscriptions->update(
                        $subscription->stripe_subscription_id,
                        ['pause_collection' => ['behavior' => 'mark_uncollectible']]
                    );
                }

                $subscription->markAsPaused();

                Log::info('Subscription paused', [
                    'subscription_id' => $subscription->id,
                ]);

                return [
                    'success' => true,
                    'subscription' => $subscription->fresh(),
                ];
            });
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Failed to pause subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Process a Stripe webhook payload.
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function processWebhook(array $payload): array
    {
        $eventType = $payload['type'] ?? null;
        $data = $payload['data']['object'] ?? [];

        Log::info('Processing subscription webhook', [
            'type' => $eventType,
            'object_id' => $data['id'] ?? null,
        ]);

        try {
            return match ($eventType) {
                'invoice.paid' => $this->handleInvoicePaid($data),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($data),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($data),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($data),
                'customer.subscription.trial_will_end' => $this->handleTrialWillEnd($data),
                default => [
                    'success' => true,
                    'message' => 'Event type not handled: '.$eventType,
                ],
            };
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle invoice.paid webhook.
     */
    protected function handleInvoicePaid(array $data): array
    {
        $subscriptionId = $data['subscription'] ?? null;
        $invoiceId = $data['id'] ?? null;

        if (! $subscriptionId) {
            return ['success' => true, 'message' => 'No subscription associated'];
        }

        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();

        if (! $subscription) {
            return ['success' => true, 'message' => 'Subscription not found locally'];
        }

        // Create or update invoice record
        $invoice = SubscriptionInvoice::updateOrCreate(
            ['stripe_invoice_id' => $invoiceId],
            [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'invoice_number' => $data['number'] ?? null,
                'subtotal' => ($data['subtotal'] ?? 0) / 100,
                'tax' => ($data['tax'] ?? 0) / 100,
                'discount' => ($data['total_discount_amounts'][0]['amount'] ?? 0) / 100,
                'total' => ($data['total'] ?? 0) / 100,
                'currency' => strtoupper($data['currency'] ?? 'USD'),
                'status' => SubscriptionInvoice::STATUS_PAID,
                'pdf_url' => $data['invoice_pdf'] ?? null,
                'hosted_invoice_url' => $data['hosted_invoice_url'] ?? null,
                'period_start' => isset($data['period_start']) ? Carbon::createFromTimestamp($data['period_start']) : null,
                'period_end' => isset($data['period_end']) ? Carbon::createFromTimestamp($data['period_end']) : null,
                'paid_at' => now(),
                'payment_intent_id' => $data['payment_intent'] ?? null,
            ]
        );

        // Update subscription status
        if ($subscription->status !== Subscription::STATUS_ACTIVE) {
            $subscription->markAsActive();
        }

        Log::info('Invoice paid webhook processed', [
            'invoice_id' => $invoice->id,
            'subscription_id' => $subscription->id,
        ]);

        return ['success' => true, 'message' => 'Invoice paid processed'];
    }

    /**
     * Handle invoice.payment_failed webhook.
     */
    protected function handleInvoicePaymentFailed(array $data): array
    {
        $subscriptionId = $data['subscription'] ?? null;

        if (! $subscriptionId) {
            return ['success' => true, 'message' => 'No subscription associated'];
        }

        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();

        if (! $subscription) {
            return ['success' => true, 'message' => 'Subscription not found locally'];
        }

        // Update subscription status to past_due
        $subscription->update(['status' => Subscription::STATUS_PAST_DUE]);

        // Create invoice record for failed payment
        $invoiceId = $data['id'] ?? null;
        if ($invoiceId) {
            SubscriptionInvoice::updateOrCreate(
                ['stripe_invoice_id' => $invoiceId],
                [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'subtotal' => ($data['subtotal'] ?? 0) / 100,
                    'tax' => ($data['tax'] ?? 0) / 100,
                    'total' => ($data['total'] ?? 0) / 100,
                    'currency' => strtoupper($data['currency'] ?? 'USD'),
                    'status' => SubscriptionInvoice::STATUS_OPEN,
                    'hosted_invoice_url' => $data['hosted_invoice_url'] ?? null,
                ]
            );
        }

        // Send notification to user about failed payment
        $user = $subscription->user;
        if ($user) {
            $user->notify(new \App\Notifications\SubscriptionPaymentFailedNotification($subscription));
        }

        Log::warning('Invoice payment failed', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
        ]);

        return ['success' => true, 'message' => 'Payment failure processed'];
    }

    /**
     * Handle customer.subscription.updated webhook.
     */
    protected function handleSubscriptionUpdated(array $data): array
    {
        $subscription = Subscription::where('stripe_subscription_id', $data['id'])->first();

        if (! $subscription) {
            return ['success' => true, 'message' => 'Subscription not found locally'];
        }

        $subscription->update([
            'status' => $this->mapStripeStatus($data['status']),
            'current_period_start' => Carbon::createFromTimestamp($data['current_period_start']),
            'current_period_end' => Carbon::createFromTimestamp($data['current_period_end']),
            'cancel_at_period_end' => $data['cancel_at_period_end'] ?? false,
            'trial_ends_at' => isset($data['trial_end']) ? Carbon::createFromTimestamp($data['trial_end']) : null,
        ]);

        Log::info('Subscription updated via webhook', [
            'subscription_id' => $subscription->id,
            'status' => $data['status'],
        ]);

        return ['success' => true, 'message' => 'Subscription updated'];
    }

    /**
     * Handle customer.subscription.deleted webhook.
     */
    protected function handleSubscriptionDeleted(array $data): array
    {
        $subscription = Subscription::where('stripe_subscription_id', $data['id'])->first();

        if (! $subscription) {
            return ['success' => true, 'message' => 'Subscription not found locally'];
        }

        $subscription->markAsCanceled('Canceled via Stripe', true);

        Log::info('Subscription deleted via webhook', [
            'subscription_id' => $subscription->id,
        ]);

        return ['success' => true, 'message' => 'Subscription deleted'];
    }

    /**
     * Handle customer.subscription.trial_will_end webhook.
     */
    protected function handleTrialWillEnd(array $data): array
    {
        $subscription = Subscription::where('stripe_subscription_id', $data['id'])->first();

        if (! $subscription) {
            return ['success' => true, 'message' => 'Subscription not found locally'];
        }

        // Send notification to user about trial ending
        $user = $subscription->user;
        if ($user) {
            $user->notify(new \App\Notifications\SubscriptionTrialEndingNotification(
                $subscription,
                $data['trial_end'] ?? null
            ));
        }

        Log::info('Trial will end notification', [
            'subscription_id' => $subscription->id,
            'trial_end' => $data['trial_end'],
        ]);

        return ['success' => true, 'message' => 'Trial ending notification processed'];
    }

    /**
     * Check if a user has access to a specific feature.
     */
    public function checkFeatureAccess(User $user, string $feature): bool
    {
        $subscription = $this->getActiveSubscription($user);

        if (! $subscription) {
            return false;
        }

        return $subscription->hasFeature($feature);
    }

    /**
     * Get features available to a user.
     *
     * @return array<string>
     */
    public function getUserFeatures(User $user): array
    {
        $subscription = $this->getActiveSubscription($user);

        if (! $subscription || ! $subscription->plan) {
            return [];
        }

        return $subscription->plan->features ?? [];
    }

    /**
     * Get subscription metrics for admin dashboard.
     *
     * @return array<string, mixed>
     */
    public function getSubscriptionMetrics(): array
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        // Active subscriptions by status
        $statusCounts = Subscription::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Active subscriptions by plan type
        $byPlanType = Subscription::query()
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->selectRaw('subscription_plans.type, COUNT(*) as count')
            ->where('subscriptions.status', Subscription::STATUS_ACTIVE)
            ->groupBy('subscription_plans.type')
            ->pluck('count', 'type')
            ->toArray();

        // Revenue metrics
        $monthlyRevenue = SubscriptionInvoice::query()
            ->where('status', SubscriptionInvoice::STATUS_PAID)
            ->where('paid_at', '>=', $thirtyDaysAgo)
            ->sum('total');

        $previousMonthRevenue = SubscriptionInvoice::query()
            ->where('status', SubscriptionInvoice::STATUS_PAID)
            ->whereBetween('paid_at', [$thirtyDaysAgo->copy()->subDays(30), $thirtyDaysAgo])
            ->sum('total');

        // New subscriptions this month
        $newSubscriptions = Subscription::query()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        // Churned subscriptions this month
        $churnedSubscriptions = Subscription::query()
            ->where('status', Subscription::STATUS_CANCELED)
            ->where('canceled_at', '>=', $thirtyDaysAgo)
            ->count();

        // Calculate churn rate
        $totalAtStart = Subscription::query()
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_CANCELED])
            ->where('created_at', '<', $thirtyDaysAgo)
            ->count();

        $churnRate = $totalAtStart > 0 ? round(($churnedSubscriptions / $totalAtStart) * 100, 2) : 0;

        // MRR (Monthly Recurring Revenue)
        $mrr = Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->selectRaw('SUM(CASE
                WHEN subscription_plans.interval = "monthly" THEN subscription_plans.price
                WHEN subscription_plans.interval = "quarterly" THEN subscription_plans.price / 3
                WHEN subscription_plans.interval = "yearly" THEN subscription_plans.price / 12
                ELSE subscription_plans.price
            END) as mrr')
            ->value('mrr') ?? 0;

        // Trials ending soon
        $trialsEndingSoon = Subscription::query()
            ->where('status', Subscription::STATUS_TRIALING)
            ->where('trial_ends_at', '<=', $now->copy()->addDays(7))
            ->count();

        // Average subscription value
        $avgSubscriptionValue = Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->avg('subscription_plans.price') ?? 0;

        return [
            'total_active' => $statusCounts[Subscription::STATUS_ACTIVE] ?? 0,
            'total_trialing' => $statusCounts[Subscription::STATUS_TRIALING] ?? 0,
            'total_past_due' => $statusCounts[Subscription::STATUS_PAST_DUE] ?? 0,
            'total_canceled' => $statusCounts[Subscription::STATUS_CANCELED] ?? 0,
            'status_counts' => $statusCounts,
            'by_plan_type' => $byPlanType,
            'monthly_revenue' => round($monthlyRevenue, 2),
            'previous_month_revenue' => round($previousMonthRevenue, 2),
            'revenue_growth' => $previousMonthRevenue > 0
                ? round((($monthlyRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100, 2)
                : 0,
            'new_subscriptions' => $newSubscriptions,
            'churned_subscriptions' => $churnedSubscriptions,
            'churn_rate' => $churnRate,
            'mrr' => round($mrr, 2),
            'arr' => round($mrr * 12, 2),
            'trials_ending_soon' => $trialsEndingSoon,
            'avg_subscription_value' => round($avgSubscriptionValue, 2),
        ];
    }

    /**
     * Grant a complimentary subscription to a user (admin action).
     *
     * @return array{success: bool, subscription?: Subscription, error?: string}
     */
    public function grantComplimentarySubscription(
        User $user,
        SubscriptionPlan $plan,
        int $durationDays,
        string $reason
    ): array {
        try {
            // Check for existing active subscription
            $existingSubscription = $this->getActiveSubscription($user);
            if ($existingSubscription) {
                return [
                    'success' => false,
                    'error' => 'User already has an active subscription.',
                ];
            }

            $now = now();
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'current_period_start' => $now,
                'current_period_end' => $now->copy()->addDays($durationDays),
                'metadata' => [
                    'complimentary' => true,
                    'granted_by' => auth()->id(),
                    'reason' => $reason,
                    'granted_at' => $now->toIso8601String(),
                ],
            ]);

            Log::info('Complimentary subscription granted', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'duration_days' => $durationDays,
                'granted_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'subscription' => $subscription,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to grant complimentary subscription', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to grant subscription: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get user's subscription invoices.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserInvoices(User $user, int $limit = 10)
    {
        return SubscriptionInvoice::where('user_id', $user->id)
            ->with('subscription.plan')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Update payment method for a subscription.
     *
     * @return array{success: bool, error?: string}
     */
    public function updatePaymentMethod(Subscription $subscription, string $paymentMethodId): array
    {
        try {
            if (! $subscription->stripe_customer_id) {
                return [
                    'success' => false,
                    'error' => 'No Stripe customer associated with this subscription.',
                ];
            }

            // Attach new payment method to customer
            $this->stripe->paymentMethods->attach(
                $paymentMethodId,
                ['customer' => $subscription->stripe_customer_id]
            );

            // Set as default payment method
            $this->stripe->customers->update(
                $subscription->stripe_customer_id,
                ['invoice_settings' => ['default_payment_method' => $paymentMethodId]]
            );

            // Update subscription's default payment method
            if ($subscription->stripe_subscription_id) {
                $this->stripe->subscriptions->update(
                    $subscription->stripe_subscription_id,
                    ['default_payment_method' => $paymentMethodId]
                );
            }

            Log::info('Payment method updated', [
                'subscription_id' => $subscription->id,
            ]);

            return ['success' => true];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Failed to update payment method', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Get or create Stripe customer for a user.
     */
    protected function ensureStripeCustomer(User $user, ?string $paymentMethodId = null): string
    {
        // Check if user already has a Stripe customer ID
        if ($user->stripe_id) {
            return $user->stripe_id;
        }

        // Create new Stripe customer
        $customerData = [
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
                'user_type' => $user->user_type,
                'platform' => 'overtimestaff',
            ],
        ];

        if ($paymentMethodId) {
            $customerData['payment_method'] = $paymentMethodId;
            $customerData['invoice_settings'] = [
                'default_payment_method' => $paymentMethodId,
            ];
        }

        $customer = $this->stripe->customers->create($customerData);

        // Save Stripe customer ID to user
        $user->update(['stripe_id' => $customer->id]);

        return $customer->id;
    }

    /**
     * Map Stripe subscription status to local status.
     */
    protected function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'active' => Subscription::STATUS_ACTIVE,
            'past_due' => Subscription::STATUS_PAST_DUE,
            'canceled' => Subscription::STATUS_CANCELED,
            'trialing' => Subscription::STATUS_TRIALING,
            'paused' => Subscription::STATUS_PAUSED,
            'incomplete' => Subscription::STATUS_INCOMPLETE,
            'incomplete_expired' => Subscription::STATUS_INCOMPLETE_EXPIRED,
            'unpaid' => Subscription::STATUS_UNPAID,
            default => Subscription::STATUS_INCOMPLETE,
        };
    }

    /**
     * Format Stripe error for user display.
     */
    protected function formatStripeError(\Stripe\Exception\ApiErrorException $e): string
    {
        $stripeCode = $e->getStripeCode();

        $friendlyMessages = [
            'card_declined' => 'Your card was declined. Please try a different payment method.',
            'insufficient_funds' => 'Your card has insufficient funds.',
            'expired_card' => 'Your card has expired. Please use a different card.',
            'incorrect_cvc' => 'The CVC code is incorrect.',
            'processing_error' => 'An error occurred while processing your card. Please try again.',
            'rate_limit' => 'Too many requests. Please wait a moment and try again.',
        ];

        if ($stripeCode && isset($friendlyMessages[$stripeCode])) {
            return $friendlyMessages[$stripeCode];
        }

        return 'A payment error occurred. Please try again or contact support.';
    }
}
