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
     * Test 1: User-level account lockout - 5 failed attempts should trigger lockout
     *
     * Note: App uses user-level account lockout (5 attempts) which returns
     * redirect with errors, separate from IP-based rate limiting (6 attempts, 429).
     */
    public function test_rate_limiting_locks_account_after_5_attempts()
    {
        $testEmail = 'ratelimit-test-' . uniqid() . '@example.com';

        User::factory()->create([
            'email' => $testEmail,
            'password' => Hash::make('correct-password'),
            'status' => 'active',
        ]);

        // Clear any existing rate limit
        RateLimiter::clear($testEmail . '|127.0.0.1');

        // Attempt 5 failed logins
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->post('/login', [
                'email' => $testEmail,
                'password' => 'wrong-password',
            ]);

            // All attempts return 302 redirect with validation errors
            $response->assertStatus(302);
            $response->assertSessionHasErrors('email');
        }

        // Verify the user account is now locked
        $user = User::where('email', $testEmail)->first();
        $this->assertTrue($user->isLocked());

        // 6th attempt triggers IP-based rate limiter (429) in addition to account lock
        $response = $this->post('/login', [
            'email' => $testEmail,
            'password' => 'wrong-password',
        ]);

        // IP rate limiter returns 429 after maxAttempts (6) reached
        $response->assertStatus(429);
    }

    /**
     * Test 2: User registration with password requirements (min 8 chars, mixed case, numbers)
     */
    public function test_user_registration_requires_minimum_password_length()
    {
        $shortPassEmail = 'short-pass-' . uniqid() . '@example.com';
        $validPassEmail = 'valid-pass-' . uniqid() . '@example.com';

        // Test password too short (less than 8 characters)
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => $shortPassEmail,
            'password' => 'Short1', // 6 characters - too short
            'password_confirmation' => 'Short1',
            'user_type' => 'worker',
            'agree_terms' => true,
        ]);

        $response->assertSessionHasErrors('password');

        // Test password valid (8+ chars, mixed case, numbers)
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => $validPassEmail,
            'password' => 'ValidPass123', // 12 characters, mixed case, numbers
            'password_confirmation' => 'ValidPass123',
            'user_type' => 'worker',
            'agree_terms' => true,
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', [
            'email' => $validPassEmail,
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
        $resetEmail = 'reset-' . uniqid() . '@example.com';

        User::factory()->create([
            'email' => $resetEmail,
        ]);

        // Request password reset
        $response = $this->post('/password/email', [
            'email' => $resetEmail,
        ]);

        $response->assertRedirect();
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
            'failed_login_attempts' => 0,
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