<?php

namespace Tests\Feature\Regression;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression Tests for Critical Routes
 *
 * PRIORITY-0: Minimal test suite to prevent regressions in top routes
 * Tests the most critical user-facing and API endpoints
 */
class CriticalRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test homepage loads successfully.
     */
    public function test_homepage_loads(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');
    }

    /**
     * Test login page loads.
     */
    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * Test registration page loads.
     */
    public function test_registration_page_loads(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    /**
     * Test worker dashboard requires authentication.
     */
    public function test_worker_dashboard_requires_auth(): void
    {
        $response = $this->get('/worker/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Test worker dashboard loads for authenticated worker.
     */
    public function test_worker_dashboard_loads_for_authenticated_worker(): void
    {
        $user = User::factory()->create();
        // Create worker profile to make user a worker
        $user->workerProfile()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        $this->actingAs($user);

        $response = $this->get('/worker/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Test business dashboard requires authentication.
     */
    public function test_business_dashboard_requires_auth(): void
    {
        $response = $this->get('/company/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Test business dashboard loads for authenticated business.
     */
    public function test_business_dashboard_loads_for_authenticated_business(): void
    {
        $user = User::factory()->create();
        // Create business profile to make user a business
        $user->businessProfile()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        $this->actingAs($user);

        $response = $this->get('/company/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Test admin dashboard requires authentication and admin role.
     */
    public function test_admin_dashboard_requires_auth_and_role(): void
    {
        // Not authenticated
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');

        // Authenticated but not admin
        $user = User::factory()->create();
        $user->workerProfile()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');
        $response->assertForbidden();
    }

    /**
     * Test API user endpoint requires authentication.
     */
    public function test_api_user_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'error_code' => 'UNAUTHENTICATED',
        ]);
    }

    /**
     * Test API user endpoint returns user for authenticated user.
     */
    public function test_api_user_endpoint_returns_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'name',
            'email',
        ]);
    }

    /**
     * Test API dashboard stats requires authentication.
     */
    public function test_api_dashboard_stats_requires_auth(): void
    {
        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(401);
    }

    /**
     * Test API dashboard stats returns data for authenticated user.
     */
    public function test_api_dashboard_stats_returns_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(200);
        // May return different structures, just check it's successful
        $this->assertTrue($response->isSuccessful());
    }

    /**
     * Test 404 returns proper JSON for API requests.
     */
    public function test_api_404_returns_json(): void
    {
        $response = $this->getJson('/api/nonexistent');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error_code' => 'NOT_FOUND',
        ]);
    }

    /**
     * Test webhook routes are accessible without CSRF.
     */
    public function test_stripe_webhook_route_exists(): void
    {
        $response = $this->postJson('/webhook/stripe/subscription', []);

        // Should not be 404 (route exists)
        // May return 400/401 for invalid signature, but not 404
        $this->assertNotEquals(404, $response->status());
    }

    /**
     * Test withdrawal route requires authentication.
     */
    public function test_withdrawal_route_requires_auth(): void
    {
        $response = $this->get('/worker/withdraw');

        $response->assertRedirect('/login');
    }

    /**
     * Test withdrawal route requires worker role.
     */
    public function test_withdrawal_route_requires_worker_role(): void
    {
        $user = User::factory()->create();
        $user->businessProfile()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->get('/worker/withdraw');

        $response->assertForbidden();
    }
}
