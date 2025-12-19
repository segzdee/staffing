<?php

namespace Tests\Feature\Worker;

use Tests\TestCase;
use App\Models\User;
use App\Models\Shift;
use App\Models\WorkerProfile;
use App\Models\BusinessProfile;
use App\Http\Middleware\EnsureWorkerActivated;
use Tests\Traits\DatabaseMigrationsWithTransactions;

/**
 * EnsureWorkerActivated Middleware Test
 * STAFF-REG-011: Worker Account Activation
 *
 * Tests the middleware that gates shift actions for activated workers.
 * The middleware is applied to specific routes: market.apply, market.claim
 */
class EnsureWorkerActivatedMiddlewareTest extends TestCase
{
    use DatabaseMigrationsWithTransactions;

    protected EnsureWorkerActivated $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeMigrations();
        $this->middleware = new EnsureWorkerActivated();
    }

    /**
     * Helper to create an activated worker.
     */
    protected function createActivatedWorker(): User
    {
        $user = User::factory()->create([
            'user_type' => 'worker',
            'email_verified_at' => now(),
        ]);

        WorkerProfile::factory()->create([
            'user_id' => $user->id,
            'is_activated' => true,
            'activated_at' => now(),
            'is_matching_eligible' => true,
        ]);

        return $user->fresh('workerProfile');
    }

    /**
     * Helper to create a non-activated worker.
     */
    protected function createNonActivatedWorker(): User
    {
        $user = User::factory()->create([
            'user_type' => 'worker',
            'email_verified_at' => now(),
        ]);

        WorkerProfile::factory()->create([
            'user_id' => $user->id,
            'is_activated' => false,
            'is_matching_eligible' => false,
        ]);

        return $user->fresh('workerProfile');
    }

    /**
     * Helper to create a business and shift for testing.
     */
    protected function createShiftForTesting(): Shift
    {
        $business = User::factory()->create(['user_type' => 'business']);
        BusinessProfile::factory()->create(['user_id' => $business->id]);

        return Shift::factory()->create([
            'business_id' => $business->id,
            'status' => 'open',
            'shift_date' => now()->addDays(1),
        ]);
    }

    /** @test */
    public function non_workers_can_access_routes_without_activation()
    {
        $business = User::factory()->create(['user_type' => 'business']);
        BusinessProfile::factory()->create(['user_id' => $business->id]);

        $response = $this->actingAs($business)
            ->get('/business/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function activated_worker_can_access_dashboard()
    {
        $worker = $this->createActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function non_activated_worker_can_access_dashboard()
    {
        // Dashboard is exempt from activation check
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function non_activated_worker_can_access_profile_routes()
    {
        // Profile routes are exempt from activation check
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/profile');

        $response->assertStatus(200);
    }

    /** @test */
    public function non_activated_worker_can_access_activation_routes()
    {
        // Activation routes are exempt from activation check
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/activation');

        // Activation page may redirect to a specific step or show the page
        // Either 200 or 302 redirect is acceptable (not 403/401)
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 302]),
            "Expected 200 or 302, got {$response->getStatusCode()}"
        );
    }

    /** @test */
    public function non_activated_worker_can_access_kyc_routes()
    {
        // KYC routes are exempt from activation check
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/kyc');

        $response->assertStatus(200);
    }

    /** @test */
    public function non_activated_worker_can_access_payment_setup()
    {
        // Payment setup is exempt from activation check
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/payment-setup');

        $response->assertStatus(200);
    }

    /** @test */
    public function non_activated_worker_can_access_skills_routes()
    {
        // Skills routes are exempt from activation check
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/skills');

        $response->assertStatus(200);
    }

    /** @test */
    public function non_activated_worker_can_access_certifications()
    {
        // Certifications routes are exempt from activation check
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/certifications');

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_users_are_not_affected_by_middleware()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /** @test */
    public function middleware_has_correct_exempt_routes_configured()
    {
        $middleware = new EnsureWorkerActivated();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('exemptRoutes');
        $property->setAccessible(true);
        $exemptRoutes = $property->getValue($middleware);

        // Verify key exempt routes are configured
        $this->assertContains('worker.dashboard', $exemptRoutes);
        $this->assertContains('worker.profile.*', $exemptRoutes);
        $this->assertContains('worker.onboarding.*', $exemptRoutes);
        $this->assertContains('worker.activation.*', $exemptRoutes);
        $this->assertContains('worker.kyc.*', $exemptRoutes);
        $this->assertContains('worker.payment-setup.*', $exemptRoutes);
        $this->assertContains('worker.skills.*', $exemptRoutes);
        $this->assertContains('worker.certifications.*', $exemptRoutes);
        $this->assertContains('worker.availability.*', $exemptRoutes);
    }
}
