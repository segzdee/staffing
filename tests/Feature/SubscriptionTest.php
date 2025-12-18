<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FIN-011: Subscription System Tests
 *
 * Tests for the subscription billing system including:
 * - Plan retrieval
 * - Subscription creation
 * - Subscription management
 * - Feature access control
 * - Webhook handling
 */
class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected User $worker;

    protected User $business;

    protected User $admin;

    protected SubscriptionPlan $workerPlan;

    protected SubscriptionPlan $businessPlan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->worker = User::factory()->create([
            'user_type' => 'worker',
            'email_verified_at' => now(),
        ]);

        $this->business = User::factory()->create([
            'user_type' => 'business',
            'email_verified_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'user_type' => 'admin',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create test plans
        $this->workerPlan = SubscriptionPlan::create([
            'name' => 'Worker Pro',
            'slug' => 'worker-pro-monthly',
            'type' => 'worker',
            'interval' => 'monthly',
            'price' => 9.99,
            'currency' => 'USD',
            'features' => ['priority_matching', 'early_payout'],
            'trial_days' => 7,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->businessPlan = SubscriptionPlan::create([
            'name' => 'Business Essential',
            'slug' => 'business-essential-monthly',
            'type' => 'business',
            'interval' => 'monthly',
            'price' => 49.99,
            'currency' => 'USD',
            'features' => ['unlimited_posts', 'analytics'],
            'trial_days' => 14,
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    // ==================== PLAN TESTS ====================

    public function test_worker_can_view_worker_plans(): void
    {
        $response = $this->actingAs($this->worker)
            ->get(route('subscription.plans'));

        $response->assertStatus(200);
        $response->assertSee('Worker Pro');
        $response->assertDontSee('Business Essential');
    }

    public function test_business_can_view_business_plans(): void
    {
        $response = $this->actingAs($this->business)
            ->get(route('subscription.plans'));

        $response->assertStatus(200);
        $response->assertSee('Business Essential');
        $response->assertDontSee('Worker Pro');
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get(route('subscription.plans'));

        $response->assertRedirect(route('login'));
    }

    // ==================== SUBSCRIPTION SERVICE TESTS ====================

    public function test_get_available_plans_returns_correct_type(): void
    {
        $service = app(SubscriptionService::class);

        $workerPlans = $service->getAvailablePlans($this->worker);

        $this->assertCount(1, $workerPlans);
        $this->assertEquals('worker', $workerPlans->first()->type);

        $businessPlans = $service->getAvailablePlans($this->business);

        $this->assertCount(1, $businessPlans);
        $this->assertEquals('business', $businessPlans->first()->type);
    }

    public function test_get_available_plans_excludes_inactive(): void
    {
        $inactivePlan = SubscriptionPlan::create([
            'name' => 'Worker Plus',
            'slug' => 'worker-plus-monthly',
            'type' => 'worker',
            'interval' => 'monthly',
            'price' => 19.99,
            'currency' => 'USD',
            'features' => ['feature1'],
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $service = app(SubscriptionService::class);
        $plans = $service->getAvailablePlans($this->worker);

        $this->assertCount(1, $plans);
        $this->assertFalse($plans->contains('id', $inactivePlan->id));
    }

    public function test_has_active_subscription_returns_false_without_subscription(): void
    {
        $service = app(SubscriptionService::class);

        $this->assertFalse($service->hasActiveSubscription($this->worker));
    }

    public function test_has_active_subscription_returns_true_with_subscription(): void
    {
        Subscription::create([
            'user_id' => $this->worker->id,
            'subscription_plan_id' => $this->workerPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $service = app(SubscriptionService::class);

        $this->assertTrue($service->hasActiveSubscription($this->worker));
    }

    // ==================== SUBSCRIPTION MODEL TESTS ====================

    public function test_subscription_is_valid_when_active(): void
    {
        $subscription = Subscription::create([
            'user_id' => $this->worker->id,
            'subscription_plan_id' => $this->workerPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->assertTrue($subscription->isValid());
    }

    public function test_subscription_is_valid_when_on_trial(): void
    {
        $subscription = Subscription::create([
            'user_id' => $this->worker->id,
            'subscription_plan_id' => $this->workerPlan->id,
            'status' => Subscription::STATUS_TRIALING,
            'trial_ends_at' => now()->addDays(7),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->assertTrue($subscription->isValid());
        $this->assertTrue($subscription->onTrial());
    }

    public function test_subscription_is_not_valid_when_canceled_and_ended(): void
    {
        $subscription = Subscription::create([
            'user_id' => $this->worker->id,
            'subscription_plan_id' => $this->workerPlan->id,
            'status' => Subscription::STATUS_CANCELED,
            'canceled_at' => now()->subDays(7),
            'ends_at' => now()->subDay(),
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->subDay(),
        ]);

        $this->assertFalse($subscription->isValid());
        $this->assertTrue($subscription->hasEnded());
    }

    public function test_subscription_has_feature(): void
    {
        $subscription = Subscription::create([
            'user_id' => $this->worker->id,
            'subscription_plan_id' => $this->workerPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->assertTrue($subscription->hasFeature('priority_matching'));
        $this->assertTrue($subscription->hasFeature('early_payout'));
        $this->assertFalse($subscription->hasFeature('non_existent_feature'));
    }

    public function test_subscription_days_remaining_calculation(): void
    {
        $subscription = Subscription::create([
            'user_id' => $this->worker->id,
            'subscription_plan_id' => $this->workerPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(15),
        ]);

        $daysRemaining = $subscription->daysRemaining();

        $this->assertGreaterThanOrEqual(14, $daysRemaining);
        $this->assertLessThanOrEqual(15, $daysRemaining);
    }

    // ==================== SUBSCRIPTION PLAN MODEL TESTS ====================

    public function test_plan_has_feature(): void
    {
        $this->assertTrue($this->workerPlan->hasFeature('priority_matching'));
        $this->assertFalse($this->workerPlan->hasFeature('non_existent'));
    }

    public function test_plan_price_in_cents(): void
    {
        $this->assertEquals(999, $this->workerPlan->getPriceInCents());
        $this->assertEquals(4999, $this->businessPlan->getPriceInCents());
    }

    public function test_plan_formatted_price(): void
    {
        $this->assertEquals('$9.99', $this->workerPlan->formatted_price);
        $this->assertEquals('$49.99', $this->businessPlan->formatted_price);
    }

    public function test_plan_type_checks(): void
    {
        $this->assertTrue($this->workerPlan->isWorkerPlan());
        $this->assertFalse($this->workerPlan->isBusinessPlan());

        $this->assertTrue($this->businessPlan->isBusinessPlan());
        $this->assertFalse($this->businessPlan->isWorkerPlan());
    }

    // ==================== FEATURE ACCESS TESTS ====================

    public function test_check_feature_access_without_subscription(): void
    {
        $service = app(SubscriptionService::class);

        $this->assertFalse($service->checkFeatureAccess($this->worker, 'priority_matching'));
    }

    public function test_check_feature_access_with_subscription(): void
    {
        Subscription::create([
            'user_id' => $this->worker->id,
            'subscription_plan_id' => $this->workerPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $service = app(SubscriptionService::class);

        $this->assertTrue($service->checkFeatureAccess($this->worker, 'priority_matching'));
        $this->assertTrue($service->checkFeatureAccess($this->worker, 'early_payout'));
        $this->assertFalse($service->checkFeatureAccess($this->worker, 'unlimited_posts'));
    }

    // ==================== INVOICE TESTS ====================

    public function test_invoice_status_checks(): void
    {
        $subscription = Subscription::create([
            'user_id' => $this->worker->id,
            'subscription_plan_id' => $this->workerPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $paidInvoice = SubscriptionInvoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $this->worker->id,
            'subtotal' => 9.99,
            'tax' => 0,
            'total' => 9.99,
            'currency' => 'USD',
            'status' => SubscriptionInvoice::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $openInvoice = SubscriptionInvoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $this->worker->id,
            'subtotal' => 9.99,
            'tax' => 0,
            'total' => 9.99,
            'currency' => 'USD',
            'status' => SubscriptionInvoice::STATUS_OPEN,
            'due_date' => now()->addDays(7),
        ]);

        $this->assertTrue($paidInvoice->isPaid());
        $this->assertFalse($paidInvoice->isOpen());

        $this->assertTrue($openInvoice->isOpen());
        $this->assertFalse($openInvoice->isPaid());
        $this->assertFalse($openInvoice->isPastDue());
    }

    public function test_invoice_past_due(): void
    {
        $subscription = Subscription::create([
            'user_id' => $this->worker->id,
            'subscription_plan_id' => $this->workerPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $pastDueInvoice = SubscriptionInvoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $this->worker->id,
            'subtotal' => 9.99,
            'tax' => 0,
            'total' => 9.99,
            'currency' => 'USD',
            'status' => SubscriptionInvoice::STATUS_OPEN,
            'due_date' => now()->subDays(3),
        ]);

        $this->assertTrue($pastDueInvoice->isPastDue());
    }

    // ==================== SUBSCRIPTION METRICS TESTS ====================

    public function test_subscription_metrics(): void
    {
        // Create some test subscriptions
        Subscription::create([
            'user_id' => $this->worker->id,
            'subscription_plan_id' => $this->workerPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        Subscription::create([
            'user_id' => $this->business->id,
            'subscription_plan_id' => $this->businessPlan->id,
            'status' => Subscription::STATUS_TRIALING,
            'trial_ends_at' => now()->addDays(14),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $service = app(SubscriptionService::class);
        $metrics = $service->getSubscriptionMetrics();

        $this->assertEquals(1, $metrics['total_active']);
        $this->assertEquals(1, $metrics['total_trialing']);
        $this->assertArrayHasKey('mrr', $metrics);
        $this->assertArrayHasKey('arr', $metrics);
        $this->assertArrayHasKey('churn_rate', $metrics);
    }

    // ==================== COMPLIMENTARY SUBSCRIPTION TESTS ====================

    public function test_grant_complimentary_subscription(): void
    {
        $service = app(SubscriptionService::class);

        $this->actingAs($this->admin);

        $result = $service->grantComplimentarySubscription(
            $this->worker,
            $this->workerPlan,
            30,
            'Testing'
        );

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Subscription::class, $result['subscription']);
        $this->assertEquals(Subscription::STATUS_ACTIVE, $result['subscription']->status);
        $this->assertTrue($service->hasActiveSubscription($this->worker));
    }

    public function test_cannot_grant_complimentary_with_existing_subscription(): void
    {
        Subscription::create([
            'user_id' => $this->worker->id,
            'subscription_plan_id' => $this->workerPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $service = app(SubscriptionService::class);

        $this->actingAs($this->admin);

        $result = $service->grantComplimentarySubscription(
            $this->worker,
            $this->workerPlan,
            30,
            'Testing'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already has', $result['error']);
    }

    // ==================== ADMIN ROUTES TESTS ====================

    public function test_admin_can_access_subscription_dashboard(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.subscriptions.index'));

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_subscription_dashboard(): void
    {
        $response = $this->actingAs($this->worker)
            ->get(route('admin.subscriptions.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_plans_list(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.subscriptions.plans'));

        $response->assertStatus(200);
        $response->assertSee('Worker Pro');
        $response->assertSee('Business Essential');
    }

    public function test_admin_can_view_subscriptions_list(): void
    {
        Subscription::create([
            'user_id' => $this->worker->id,
            'subscription_plan_id' => $this->workerPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.subscriptions.list'));

        $response->assertStatus(200);
        $response->assertSee($this->worker->name);
    }
}
