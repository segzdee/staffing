<?php

namespace Tests\Feature\Worker;

use Tests\TestCase;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Http\Middleware\EnsureWorkerActivated;
use Tests\Traits\DatabaseMigrationsWithTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * EnsureWorkerActivated Middleware Test
 * STAFF-REG-011: Worker Account Activation
 *
 * Tests the middleware that gates shift actions for activated workers.
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

    /** @test */
    public function non_workers_can_access_routes_without_activation()
    {
        $business = User::factory()->create(['user_type' => 'business']);

        $response = $this->actingAs($business)
            ->get('/business/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function activated_worker_can_access_protected_routes()
    {
        $worker = $this->createActivatedWorker();

        // Assuming there's a shifts route
        $response = $this->actingAs($worker)
            ->get('/worker/shifts');

        // Should not be redirected to activation page
        $response->assertStatus(200);
    }

    /** @test */
    public function non_activated_worker_cannot_access_shift_routes()
    {
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/shifts');

        $response->assertRedirect(route('worker.activation.index'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function non_activated_worker_can_access_profile_routes()
    {
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/profile');

        $response->assertStatus(200);
    }

    /** @test */
    public function non_activated_worker_can_access_onboarding_routes()
    {
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/onboarding/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function non_activated_worker_can_access_activation_routes()
    {
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/activation');

        $response->assertStatus(200);
    }

    /** @test */
    public function worker_without_matching_eligibility_is_redirected()
    {
        $worker = $this->createActivatedWorker();

        // Disable matching
        $worker->workerProfile->update([
            'is_matching_eligible' => false,
            'matching_eligibility_reason' => 'Account suspended',
        ]);

        $response = $this->actingAs($worker->fresh())
            ->get('/worker/shifts');

        $response->assertRedirect(route('worker.activation.index'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function worker_without_profile_is_redirected()
    {
        $worker = User::factory()->create(['user_type' => 'worker']);
        // No worker profile

        $response = $this->actingAs($worker)
            ->get('/worker/shifts');

        $response->assertRedirect(route('worker.activation.index'));
    }

    /** @test */
    public function middleware_returns_json_for_ajax_requests()
    {
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->getJson('/worker/shifts/browse');

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'activation_required' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'error',
            'reason',
            'redirect_url',
            'activation_required',
        ]);
    }

    /** @test */
    public function middleware_allows_kyc_routes_without_activation()
    {
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/identity-verification');

        $response->assertStatus(200);
    }

    /** @test */
    public function middleware_allows_payment_setup_routes_without_activation()
    {
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/payment-setup');

        $response->assertStatus(200);
    }

    /** @test */
    public function middleware_allows_skills_routes_without_activation()
    {
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/skills');

        $response->assertStatus(200);
    }

    /** @test */
    public function middleware_allows_certifications_routes_without_activation()
    {
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/certifications');

        $response->assertStatus(200);
    }

    /** @test */
    public function middleware_allows_availability_routes_without_activation()
    {
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/availability');

        $response->assertStatus(200);
    }

    /** @test */
    public function middleware_allows_dashboard_without_activation()
    {
        $worker = $this->createNonActivatedWorker();

        $response = $this->actingAs($worker)
            ->get('/worker/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_users_are_not_affected_by_middleware()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
