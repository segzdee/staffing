<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard as BaseSessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Custom Session Guard with Remember Token Rotation
 *
 * This guard extends Laravel's default SessionGuard to implement automatic
 * remember token rotation for enhanced security. Token rotation helps prevent
 * session fixation attacks and limits the damage if a remember token is compromised.
 *
 * Security Benefits:
 * - Each successful authentication with "remember me" generates a new token
 * - Stolen tokens become invalid after the legitimate user authenticates
 * - Reduces the window of opportunity for token-based attacks
 * - Provides defense-in-depth for persistent login sessions
 *
 * How it works:
 * 1. When a user logs in with "remember me" enabled, a new token is generated
 * 2. When a user authenticates via an existing remember token, the token is rotated
 * 3. The old token is invalidated and a new one is issued
 * 4. This ensures tokens are single-use and short-lived
 *
 * @package App\Auth
 */
class SessionGuard extends BaseSessionGuard
{
    /**
     * Cycle the remember token for the user.
     *
     * This method is called during authentication to rotate the remember token.
     * Unlike the parent implementation which may skip rotation in certain cases,
     * this implementation ALWAYS generates a new token when called.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function cycleRememberToken(Authenticatable $user): void
    {
        // Always generate a fresh token - this is the key security enhancement
        $token = $this->generateRememberToken();

        $user->setRememberToken($token);

        $this->provider->updateRememberToken($user, $token);
    }

    /**
     * Generate a new remember token.
     *
     * Creates a cryptographically secure random token.
     * The token is 60 characters of random alphanumeric characters,
     * providing approximately 357 bits of entropy.
     *
     * @return string
     */
    protected function generateRememberToken(): string
    {
        return \Illuminate\Support\Str::random(60);
    }

    /**
     * Log a user into the application.
     *
     * Overrides the parent login method to ensure token rotation occurs
     * whenever a remember token exists, regardless of whether the user
     * is logging in fresh or via an existing remember cookie.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(Authenticatable $user, $remember = false): void
    {
        // Update the session with the user's ID
        $this->updateSession($user->getAuthIdentifier());

        // If the user has an existing remember token OR they want to be remembered,
        // we rotate the token to prevent replay attacks
        if ($remember || $user->getRememberToken()) {
            $this->ensureRememberTokenIsSet($user);
        }

        // Fire the login event
        $this->fireLoginEvent($user, $remember);

        // Set the user instance on the guard
        $this->setUser($user);
    }

    /**
     * Ensure the remember token is set and fresh.
     *
     * This method guarantees that whenever we're dealing with remember
     * functionality, the token is rotated. This is more aggressive than
     * Laravel's default behavior, which only sets a token if one doesn't exist.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function ensureRememberTokenIsSet(Authenticatable $user): void
    {
        // Always cycle the token - this is the core of our security enhancement
        // Even if a token exists, we replace it with a fresh one
        $this->cycleRememberToken($user);

        // Queue the recaller cookie with the new token
        $this->queueRecallerCookie($user);
    }

    /**
     * Log the user out of the application via the remember token.
     *
     * When authenticating via remember token (cookie-based authentication),
     * we always rotate the token to ensure the old token cannot be reused.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    protected function userFromRecaller($recaller)
    {
        // Get the user from the parent implementation
        $user = parent::userFromRecaller($recaller);

        // If we successfully authenticated via remember token, rotate it immediately
        // This ensures the token in the cookie that was just used is invalidated
        if ($user !== null) {
            $this->cycleRememberToken($user);
            $this->queueRecallerCookie($user);
        }

        return $user;
    }

    /**
     * Attempt to authenticate using HTTP Basic Auth.
     *
     * Ensures token rotation happens even for HTTP Basic authentication
     * when remember functionality is involved.
     *
     * @param  string  $field
     * @param  array  $extraConditions
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function basic($field = 'email', $extraConditions = [])
    {
        $response = parent::basic($field, $extraConditions);

        // If authenticated and user has a remember token, rotate it
        if ($this->check() && $this->user()->getRememberToken()) {
            $this->cycleRememberToken($this->user());
        }

        return $response;
    }

    /**
     * Log the user out of the application.
     *
     * Override logout to ensure the remember token is properly cleared
     * and a new token is generated (which will be discarded but prevents
     * any race conditions with concurrent sessions).
     *
     * @return void
     */
    public function logout(): void
    {
        $user = $this->user();

        // Clear the session and cookie
        $this->clearUserDataFromStorage();

        // If we have a user with a remember token, invalidate it
        if (!is_null($user) && !empty($user->getRememberToken())) {
            $this->cycleRememberToken($user);
        }

        // Fire the logout event
        if (isset($this->events)) {
            $this->events->dispatch(new \Illuminate\Auth\Events\Logout($this->name, $user));
        }

        // Clear the user from the guard
        $this->user = null;
        $this->loggedOut = true;
    }

    /**
     * Remove user data from session storage.
     *
     * @return void
     */
    protected function clearUserDataFromStorage(): void
    {
        $this->session->remove($this->getName());

        if (!is_null($this->recaller())) {
            $this->getCookieJar()->queue(
                $this->getCookieJar()->forget($this->getRecallerName())
            );
        }
    }
}
