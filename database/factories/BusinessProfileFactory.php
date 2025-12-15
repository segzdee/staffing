<?php

namespace Database\Factories;

use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BusinessProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'business_name' => $this->faker->company(),
            'business_type' => $this->faker->randomElement(['independent', 'small_business', 'enterprise']),
            'industry' => $this->faker->randomElement(['hospitality', 'healthcare', 'retail', 'events', 'warehouse', 'professional']),
            'business_address' => $this->faker->streetAddress(),
            'business_city' => $this->faker->city(),
            'business_state' => $this->faker->stateAbbr(),
            'business_country' => 'US',
            'business_phone' => $this->faker->phoneNumber(),
            'ein_tax_id' => $this->faker->numerify('##-#######'),
            'rating_average' => $this->faker->randomFloat(2, 3.5, 5.0),
            'total_shifts_posted' => 0,
            'total_shifts_completed' => 0,
            'total_shifts_cancelled' => 0,
            'fill_rate' => 0.00,
            'is_verified' => false,
            'verified_at' => null,

            // BIZ-001: Onboarding
            'onboarding_completed' => true,
            'onboarding_step' => null,
            'onboarding_completed_at' => now()->subDays(rand(1, 30)),
            'verification_status' => 'pending',
            'verification_notes' => null,
            'business_license_url' => null,
            'insurance_certificate_url' => null,
            'tax_document_url' => null,
            'documents_submitted_at' => null,

            // BIZ-002: Venues
            'multi_location_enabled' => false,
            'active_venues' => 0,

            // BIZ-003: Templates
            'total_templates' => 0,
            'active_templates' => 0,

            // BIZ-004: Ratings
            'total_reviews' => 0,
            'communication_rating' => 0.00,
            'punctuality_rating' => 0.00,
            'professionalism_rating' => 0.00,

            // BIZ-005: Analytics
            'average_shift_cost' => 0.00,
            'total_spent' => 0.00,
            'pending_payment' => 0.00,
            'unique_workers_hired' => 0,
            'repeat_workers' => 0,

            // BIZ-006: Billing
            'subscription_plan' => 'free',
            'subscription_expires_at' => null,
            'monthly_credit_limit' => null,
            'monthly_credit_used' => 0.00,
            'autopay_enabled' => false,
            'default_payment_method_id' => null,

            // BIZ-007: Worker Preferences
            'preferred_worker_ids' => [],
            'blacklisted_worker_ids' => [],
            'allow_new_workers' => true,
            'minimum_worker_rating' => 0.00,
            'minimum_shifts_completed' => 0,

            // BIZ-008: Cancellation
            'cancellation_rate' => 0.00,
            'late_cancellations' => 0,
            'total_cancellation_penalties' => 0.00,

            // BIZ-009: Support
            'open_support_tickets' => 0,
            'last_support_contact' => null,
            'priority_support' => false,

            // BIZ-010: Compliance
            'account_in_good_standing' => true,
            'account_warning_message' => null,
            'last_shift_posted_at' => null,
            'can_post_shifts' => true,
        ];
    }

    /**
     * Indicate that the business is verified.
     */
    public function verified()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_verified' => true,
                'verified_at' => now()->subDays(rand(1, 90)),
                'verification_status' => 'approved',
            ];
        });
    }

    /**
     * Indicate that the business has posted shifts.
     */
    public function withActivity()
    {
        return $this->state(function (array $attributes) {
            $posted = rand(5, 50);
            $completed = rand(3, $posted);
            $cancelled = rand(0, 3);

            return [
                'total_shifts_posted' => $posted,
                'total_shifts_completed' => $completed,
                'total_shifts_cancelled' => $cancelled,
                'fill_rate' => round($completed / $posted, 2),
                'cancellation_rate' => round(($cancelled / $posted) * 100, 2),
                'last_shift_posted_at' => now()->subDays(rand(1, 7)),
            ];
        });
    }
}
