<?php

namespace Database\Factories;

use App\Models\WorkerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkerProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WorkerProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'bio' => $this->faker->paragraph(),
            'hourly_rate_min' => $this->faker->randomFloat(2, 15, 25),
            'hourly_rate_max' => $this->faker->randomFloat(2, 25, 50),
            'industries' => ['hospitality', 'retail'],
            'availability_schedule' => [
                'monday' => ['start' => '09:00', 'end' => '17:00'],
                'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                'thursday' => ['start' => '09:00', 'end' => '17:00'],
                'friday' => ['start' => '09:00', 'end' => '17:00'],
            ],
            'transportation' => $this->faker->randomElement(['car', 'bike', 'public_transit', 'walking']),
            'max_commute_distance' => $this->faker->numberBetween(10, 50),
            'years_experience' => $this->faker->numberBetween(0, 10),
            'rating_average' => $this->faker->randomFloat(2, 3.5, 5.0),
            'total_shifts_completed' => $this->faker->numberBetween(0, 100),
            'reliability_score' => $this->faker->randomFloat(2, 80, 100),
            'total_no_shows' => 0,
            'total_cancellations' => 0,
            'background_check_status' => 'pending',
            'background_check_date' => null,
            'background_check_notes' => null,

            // WKR-001: Onboarding
            'onboarding_completed' => true,
            'onboarding_step' => null,
            'onboarding_completed_at' => now()->subDays(rand(1, 30)),
            'identity_verified' => false,
            'identity_verified_at' => null,
            'identity_verification_method' => null,

            // WKR-004: Tier system
            'subscription_tier' => 'bronze',
            'tier_expires_at' => null,
            'tier_upgraded_at' => null,

            // WKR-005: Enhanced reliability metrics
            'total_late_arrivals' => 0,
            'total_early_departures' => 0,
            'total_no_acknowledgments' => 0,
            'average_response_time_minutes' => $this->faker->numberBetween(5, 60),

            // WKR-009: Earnings tracking
            'total_earnings' => 0.00,
            'pending_earnings' => 0.00,
            'withdrawn_earnings' => 0.00,
            'average_hourly_earned' => 0.00,

            // WKR-010: Referral tracking
            'referral_code' => strtoupper($this->faker->unique()->bothify('???###')),
            'referred_by' => null,
            'total_referrals' => 0,
            'referral_earnings' => 0.00,

            // Additional profile fields
            'location_lat' => $this->faker->latitude(25, 49),
            'location_lng' => $this->faker->longitude(-125, -65),
            'location_city' => $this->faker->city(),
            'location_state' => $this->faker->stateAbbr(),
            'location_country' => 'US',
            'preferred_radius' => $this->faker->numberBetween(10, 50),
            'preferred_industries' => ['hospitality', 'retail'],
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'profile_photo_url' => null,
            'resume_url' => null,
            'linkedin_url' => null,
        ];
    }

    /**
     * Indicate that the worker is highly rated.
     */
    public function highlyRated()
    {
        return $this->state(function (array $attributes) {
            return [
                'rating_average' => $this->faker->randomFloat(2, 4.5, 5.0),
                'reliability_score' => $this->faker->randomFloat(2, 90, 100),
                'total_shifts_completed' => $this->faker->numberBetween(20, 100),
            ];
        });
    }

    /**
     * Indicate that the worker is verified.
     */
    public function verified()
    {
        return $this->state(function (array $attributes) {
            return [
                'background_check_status' => 'approved',
                'background_check_date' => now()->subDays(rand(1, 90)),
                'identity_verified' => true,
                'identity_verified_at' => now()->subDays(rand(1, 90)),
                'identity_verification_method' => 'government_id',
            ];
        });
    }
}
