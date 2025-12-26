<?php

namespace App\Services\Interfaces;

use App\Models\User;

/**
 * Onboarding Service Interface
 *
 * Defines the contract for onboarding operations.
 * All onboarding services must implement this interface.
 *
 * ARCH-004: Unified Onboarding Service Interface
 */
interface OnboardingServiceInterface
{
    /**
     * Initialize onboarding for a user.
     *
     * @param  User  $user  The user to onboard
     * @param  array  $initialData  Initial onboarding data
     * @return array Onboarding initialization result
     */
    public function initializeOnboarding(User $user, array $initialData = []): array;

    /**
     * Get current onboarding step.
     *
     * @param  User  $user  The user
     * @return string Current step identifier
     */
    public function getCurrentStep(User $user): string;

    /**
     * Get next required step.
     *
     * @param  User  $user  The user
     * @return string|null Next step identifier or null if complete
     */
    public function getNextStep(User $user): ?string;

    /**
     * Complete a step.
     *
     * @param  User  $user  The user
     * @param  string  $step  Step identifier
     * @param  array  $stepData  Step completion data
     * @return bool Success status
     *
     * @throws \Exception If step completion fails
     */
    public function completeStep(User $user, string $step, array $stepData = []): bool;

    /**
     * Check if onboarding is complete.
     *
     * @param  User  $user  The user
     * @return bool True if complete
     */
    public function isComplete(User $user): bool;

    /**
     * Get onboarding progress percentage.
     *
     * @param  User  $user  The user
     * @return float Progress percentage (0-100)
     */
    public function getProgress(User $user): float;

    /**
     * Resume onboarding from current step.
     *
     * @param  User  $user  The user
     * @return string URL to resume onboarding
     */
    public function resumeOnboarding(User $user): string;

    /**
     * Track analytics event for onboarding step.
     *
     * @param  User  $user  The user
     * @param  string  $step  Step identifier
     * @param  string  $event  Event type (started, completed, skipped, etc.)
     * @param  array  $metadata  Additional event metadata
     */
    public function trackAnalytics(User $user, string $step, string $event, array $metadata = []): void;
}
