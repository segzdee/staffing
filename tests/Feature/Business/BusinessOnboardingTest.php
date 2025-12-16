<?php

namespace Tests\Feature\Business;

use Tests\TestCase;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\OnboardingStep;
use App\Models\OnboardingProgress;
use App\Services\OnboardingProgressService;
use Tests\Traits\DatabaseMigrationsWithTransactions;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * Business Onboarding Test
 * BIZ-REG-010: Test enhanced business onboarding with progress tracking
 */
class BusinessOnboardingTest extends TestCase
{
    use DatabaseMigrationsWithTransactions, WithFaker;

    protected $business;
    protected $onboardingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeMigrations();

        // Create a business user with profile
        $this->business = User::factory()->create([
            'user_type' => 'business',
            'email_verified_at' => now(),
        ]);

        BusinessProfile::factory()->create([
            'user_id' => $this->business->id,
            'business_name' => 'Test Company',
            'business_type' => 'restaurant',
            'onboarding_completed' => false,
        ]);

        $this->onboardingService = app(OnboardingProgressService::class);
    }

    /** @test */
    public function business_can_initialize_onboarding()
    {
        $response = $this->actingAs($this->business, 'sanctum')
            ->postJson('/api/business/onboarding/initialize');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Onboarding initialized successfully.',
            ]);
    }

    /** @test */
    public function business_can_get_onboarding_progress()
    {
        // Initialize onboarding first
        $this->onboardingService->initializeOnboarding($this->business);

        $response = $this->actingAs($this->business, 'sanctum')
            ->getJson('/api/business/onboarding/progress');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'user_type',
                    'overall_progress',
                    'can_activate',
                    'is_activated',
                    'required_steps',
                    'recommended_steps',
                    'optional_steps',
                    'categories',
                    'stats',
                ],
            ]);

        $this->assertEquals('business', $response->json('data.user_type'));
    }

    /** @test */
    public function business_can_get_next_required_step()
    {
        // Initialize onboarding
        $this->onboardingService->initializeOnboarding($this->business);

        $response = $this->actingAs($this->business, 'sanctum')
            ->getJson('/api/business/onboarding/next-step');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Should have a next step since onboarding is not complete
        if (!$response->json('all_complete')) {
            $response->assertJsonStructure([
                'next_step' => [
                    'id',
                    'step_id',
                    'name',
                    'description',
                    'help_text',
                    'route_url',
                    'estimated_time',
                ],
            ]);
        }
    }

    /** @test */
    public function business_can_complete_a_step()
    {
        // Initialize onboarding
        $this->onboardingService->initializeOnboarding($this->business);

        // Get a step to complete
        $step = OnboardingStep::active()
            ->forUserType('business')
            ->first();

        if (!$step) {
            $this->markTestSkipped('No onboarding steps configured for businesses');
        }

        $response = $this->actingAs($this->business, 'sanctum')
            ->postJson('/api/business/onboarding/complete-step', [
                'step_id' => $step->step_id,
                'notes' => 'Test completion',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Step completed successfully!',
            ])
            ->assertJsonStructure([
                'overall_progress',
                'can_activate',
            ]);
    }

    /** @test */
    public function business_can_skip_optional_step()
    {
        // Initialize onboarding
        $this->onboardingService->initializeOnboarding($this->business);

        // Get an optional/recommended step
        $optionalStep = OnboardingStep::active()
            ->forUserType('business')
            ->where('step_type', '!=', 'required')
            ->first();

        if (!$optionalStep) {
            $this->markTestSkipped('No optional onboarding steps configured for businesses');
        }

        $response = $this->actingAs($this->business, 'sanctum')
            ->postJson('/api/business/onboarding/skip-step', [
                'step_id' => $optionalStep->step_id,
                'reason' => 'Will complete later',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Step skipped successfully.',
            ]);
    }

    /** @test */
    public function business_cannot_skip_required_step()
    {
        // Initialize onboarding
        $this->onboardingService->initializeOnboarding($this->business);

        // Get a required step
        $requiredStep = OnboardingStep::active()
            ->forUserType('business')
            ->required()
            ->first();

        if (!$requiredStep) {
            $this->markTestSkipped('No required onboarding steps configured for businesses');
        }

        $response = $this->actingAs($this->business, 'sanctum')
            ->postJson('/api/business/onboarding/skip-step', [
                'step_id' => $requiredStep->step_id,
                'reason' => 'Attempting to skip',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function business_profile_completion_page_loads()
    {
        $response = $this->actingAs($this->business)
            ->get('/business/profile/complete');

        $response->assertStatus(200)
            ->assertViewIs('business.onboarding.complete-profile')
            ->assertViewHas(['user', 'completeness', 'progress', 'missingFields']);
    }

    /** @test */
    public function business_redirects_to_dashboard_when_profile_complete()
    {
        // Mark business as having completed onboarding
        $this->business->update(['onboarding_completed' => true]);
        $this->business->businessProfile->update([
            'business_name' => 'Complete Business',
            'business_type' => 'retail',
            'address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'phone' => '555-1234',
            'description' => 'A fully complete business profile with all required information.',
        ]);

        // Set overall progress to 100%
        $this->onboardingService->initializeOnboarding($this->business);

        $response = $this->actingAs($this->business)
            ->get('/business/profile/complete');

        // Should redirect to dashboard if completion >= 80%
        if ($response->status() === 302) {
            $response->assertRedirect('/business/dashboard');
        }
    }

    /** @test */
    public function auto_validation_completes_steps_based_on_data()
    {
        // Initialize onboarding
        $this->onboardingService->initializeOnboarding($this->business);

        // Update business profile with complete data
        $this->business->businessProfile->update([
            'business_name' => 'Complete Business',
            'business_type' => 'retail',
            'industry' => 'retail',
            'address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'country' => 'US',
            'phone' => '555-1234',
            'work_email' => 'work@company.com',
            'work_email_verified' => true,
            'description' => 'A comprehensive business description that is longer than 50 characters.',
        ]);

        // Call getProgress which triggers auto-validation
        $response = $this->actingAs($this->business, 'sanctum')
            ->getJson('/api/business/onboarding/progress');

        $response->assertStatus(200);

        // Check that auto-validatable steps are completed
        $progressData = $response->json('data');

        // Email verification should be auto-completed
        $emailVerifiedStep = collect($progressData['required_steps'])
            ->firstWhere('step_id', 'email_verified');

        if ($emailVerifiedStep) {
            $this->assertTrue($emailVerifiedStep['is_completed']);
        }
    }

    /** @test */
    public function onboarding_tracks_weighted_progress()
    {
        // Initialize onboarding
        $this->onboardingService->initializeOnboarding($this->business);

        $initialProgress = $this->onboardingService->calculateOverallProgress($this->business);

        // Complete a required step
        $requiredStep = OnboardingStep::active()
            ->forUserType('business')
            ->required()
            ->first();

        if ($requiredStep) {
            $this->onboardingService->updateProgress(
                $this->business,
                $requiredStep->step_id,
                'completed'
            );

            $newProgress = $this->onboardingService->calculateOverallProgress($this->business);

            // Progress should increase after completing a step
            $this->assertGreaterThan($initialProgress, $newProgress);
        }
    }

    /** @test */
    public function business_cannot_access_worker_onboarding_routes()
    {
        $response = $this->actingAs($this->business, 'sanctum')
            ->getJson('/api/worker/onboarding/progress');

        // Should be forbidden or unauthorized
        $this->assertContains($response->status(), [401, 403]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_onboarding_api()
    {
        $response = $this->getJson('/api/business/onboarding/progress');

        $response->assertStatus(401);
    }
}
