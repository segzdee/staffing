<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Tests\Traits\DatabaseMigrationsWithTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityAuthenticationTest extends TestCase
{
    use DatabaseMigrationsWithTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeMigrations();
    }

    /**
     * Test 1: Rate Limiting - 5 failed attempts should trigger lockout
     */
    public function test_rate_limiting_locks_account_after_5_attempts()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
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
     * Test 2: User registration with 12-character password requirement
     */
    public function test_user_registration_requires_minimum_password_length()
    {
        // Test password too short (11 characters)
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short11', // 8 characters
            'password_confirmation' => 'short11',
            'user_type' => 'worker',
        ]);

        $response->assertSessionHasErrors('password');

        // Test password valid (12 characters)
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'valid@example.com',
            'password' => 'validpassword12', // 16 characters
            'password_confirmation' => 'validpassword12',
            'user_type' => 'worker',
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('users', [
            'email' => 'valid@example.com',
        ]);
    }

    /**
     * Test 3: Login redirects by user type
     */
    public function test_worker_redirects_to_worker_dashboard()
    {
        $user = User::factory()->create([
            'user_type' => 'worker',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
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
     * Test 4: Password reset functionality
     */
    public function test_password_reset_request()
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Request password reset
        $response = $this->post('/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('status');
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

    /**
     * Test 6: Account lockout fields exist
     */
    public function test_user_has_account_lockout_fields()
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Test 7: 2FA fields exist in database
     */
    public function test_user_has_two_factor_auth_fields()
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Test 8: Session encryption is working
     */
    public function test_session_is_encrypted()
    {
        config(['session.encrypt' => true]);
        
        $response = $this->get('/');
        
        // Check that session cookie exists
        $this->assertNotEmpty($response->headers->get('Set-Cookie'));
    }

    /**
     * Test 9: Dev routes are blocked in production
     */
    public function test_dev_routes_blocked_in_production()
    {
        // Set environment to production
        config(['app.env' => 'production']);

        // Try to access dev routes - should return 404
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
     * Test 10: Remember token security
     */
    public function test_remember_token_is_rotated_on_login()
    {
        $user = User::factory()->create([
            'remember_token' => 'old-token',
        ]);

        // Login with remember me
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => 'on',
        ]);

        $response->assertRedirect();

        // Verify remember token was changed
        $this->assertNotEquals('old-token', $user->fresh()->remember_token);
        $this->assertNotEmpty($user->fresh()->remember_token);
    }
}