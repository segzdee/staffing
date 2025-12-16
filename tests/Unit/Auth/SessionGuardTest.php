<?php

namespace Tests\Unit\Auth;

use App\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Tests for Remember Token Rotation in Custom SessionGuard
 *
 * These tests verify that the SessionGuard is properly registered
 * and configured for token rotation.
 */
class SessionGuardTest extends TestCase
{
    /**
     * Test that the web guard uses our custom SessionGuard.
     */
    public function test_web_guard_uses_custom_session_guard(): void
    {
        $guard = Auth::guard('web');

        $this->assertInstanceOf(SessionGuard::class, $guard);
    }

    /**
     * Test that the custom guard has the required methods.
     */
    public function test_custom_guard_has_token_rotation_methods(): void
    {
        $guard = Auth::guard('web');

        $reflection = new \ReflectionClass($guard);

        // Check cycleRememberToken method exists and is overridden
        $this->assertTrue($reflection->hasMethod('cycleRememberToken'));
        $method = $reflection->getMethod('cycleRememberToken');
        $this->assertEquals(SessionGuard::class, $method->getDeclaringClass()->getName());

        // Check generateRememberToken method exists
        $this->assertTrue($reflection->hasMethod('generateRememberToken'));
        $generateMethod = $reflection->getMethod('generateRememberToken');
        $this->assertEquals(SessionGuard::class, $generateMethod->getDeclaringClass()->getName());

        // Check login method is overridden
        $this->assertTrue($reflection->hasMethod('login'));
        $loginMethod = $reflection->getMethod('login');
        $this->assertEquals(SessionGuard::class, $loginMethod->getDeclaringClass()->getName());

        // Check ensureRememberTokenIsSet method exists
        $this->assertTrue($reflection->hasMethod('ensureRememberTokenIsSet'));
    }

    /**
     * Test that generateRememberToken produces tokens of correct length.
     */
    public function test_generate_remember_token_produces_correct_length(): void
    {
        $guard = Auth::guard('web');

        $reflection = new \ReflectionClass($guard);
        $method = $reflection->getMethod('generateRememberToken');
        $method->setAccessible(true);

        $token = $method->invoke($guard);

        $this->assertEquals(60, strlen($token));
    }

    /**
     * Test that generateRememberToken produces unique tokens.
     */
    public function test_generate_remember_token_produces_unique_tokens(): void
    {
        $guard = Auth::guard('web');

        $reflection = new \ReflectionClass($guard);
        $method = $reflection->getMethod('generateRememberToken');
        $method->setAccessible(true);

        $tokens = [];
        for ($i = 0; $i < 100; $i++) {
            $tokens[] = $method->invoke($guard);
        }

        // All tokens should be unique
        $this->assertEquals(100, count(array_unique($tokens)));
    }

    /**
     * Test that config uses session-rotating driver.
     */
    public function test_auth_config_uses_session_rotating_driver(): void
    {
        $driver = config('auth.guards.web.driver');

        $this->assertEquals('session-rotating', $driver);
    }

    /**
     * Test that the guard is properly constructed with all dependencies.
     */
    public function test_guard_has_required_dependencies(): void
    {
        $guard = Auth::guard('web');

        // Check cookie jar is set
        $reflection = new \ReflectionClass($guard);

        // The guard should have session store
        $sessionProperty = $reflection->getProperty('session');
        $sessionProperty->setAccessible(true);
        $this->assertNotNull($sessionProperty->getValue($guard));

        // The guard should have a provider
        $providerProperty = $reflection->getProperty('provider');
        $providerProperty->setAccessible(true);
        $this->assertNotNull($providerProperty->getValue($guard));
    }
}
