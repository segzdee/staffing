<?php

namespace Tests;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class SecurityFixesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Rate Limiting - 5 failed attempts should lock account
     */
    public function test_rate_limiting_locks_account_after_5_attempts()
    {
        // Create a test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct-password'),
            'status' => 'active',
        ]);

        // Clear any existing rate limit
        RateLimiter::clear('test@example.com|127.0.0.1');

        // Attempt 5 failed logins
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

            if ($i < 5) {
                // First 4 attempts should fail but not lock
                $response->assertStatus(302);
                $response->assertSessionHasErrors('email');
            } else {
                // 5th attempt should trigger lockout
                $response->assertStatus(429);
                $response->assertSessionHasErrors('email');
            }
        }

        // 6th attempt should also be locked out
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    /**
     * Test 2: Login redirects by user type
     */
    public function test_worker_redirects_to_worker_dashboard()
    {
        $user = User::factory()->create([
            'user_type' => 'worker',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password', // Assuming factory uses 'password'
        ]);

        $response->assertRedirect(route('worker.dashboard'));
    }

    public function test_business_redirects_to_business_dashboard()
    {
        $user = User::factory()->create([
            'user_type' => 'business',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('business.dashboard'));
    }

    public function test_agency_redirects_to_agency_dashboard()
    {
        $user = User::factory()->create([
            'user_type' => 'agency',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('agency.dashboard'));
    }

    public function test_admin_redirects_to_admin_dashboard()
    {
        $user = User::factory()->create([
            'user_type' => 'worker', // Admin uses role, not user_type
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    /**
     * Test 3: Intended URL preservation
     */
    public function test_intended_url_preserved_after_login()
    {
        // Access protected route while logged out
        $response = $this->get('/worker/dashboard');
        
        // Should redirect to login
        $response->assertRedirect(route('login'));

        // Create user
        $user = User::factory()->create([
            'user_type' => 'worker',
            'status' => 'active',
        ]);

        // Login should redirect to intended URL
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/worker/dashboard');
    }

    /**
     * Test 4: Dev routes inaccessible in production
     */
    public function test_dev_routes_inaccessible_in_production()
    {
        // Set environment to production
        config(['app.env' => 'production']);

        // Try to access dev routes
        $routes = [
            '/dev/info',
            '/dev/db-test',
            '/dev/create-test-user',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertStatus(404);
        }
    }

    /**
     * Test 5: Security logging
     */
    public function test_failed_login_attempts_are_logged()
    {
        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->with('Failed login attempt', \Mockery::type('array'));

        $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrong-password',
        ]);
    }

    public function test_successful_login_is_logged()
    {
        $user = User::factory()->create([
            'user_type' => 'worker',
            'status' => 'active',
        ]);

        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Successful login', \Mockery::type('array'));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
    }
}
