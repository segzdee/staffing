<?php

namespace Database\Factories;

use App\Models\Shift;
use App\Models\User;
use App\Models\BusinessProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Shift::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $startTime = $this->faker->time('H:i:s');
        $endTime = date('H:i:s', strtotime($startTime) + (8 * 3600)); // 8 hours later
        $shiftDate = $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d');
        $startDatetime = $shiftDate . ' ' . $startTime;
        $endDatetime = $shiftDate . ' ' . $endTime;

        return [
            // Business & Ownership
            'business_id' => User::factory()->create(['user_type' => 'business'])->id,
            'venue_id' => null,
            'posted_by_agent' => false,
            'agent_id' => null,
            'agency_client_id' => null,
            'posted_by_agency_id' => null,
            'allow_agencies' => true,
            'template_id' => null,

            // Basic Information
            'title' => $this->faker->randomElement(['Warehouse Associate', 'Server', 'Bartender', 'Security Guard', 'Cleaner']),
            'description' => $this->faker->paragraph(),
            'role_type' => $this->faker->randomElement(['Server', 'Bartender', 'Warehouse Worker', 'Security Guard', 'Cleaner']),
            'industry' => $this->faker->randomElement(['hospitality', 'healthcare', 'retail', 'events', 'warehouse', 'professional', 'logistics', 'construction', 'security', 'cleaning']),

            // Location
            'location_address' => $this->faker->streetAddress(),
            'location_city' => $this->faker->city(),
            'location_state' => $this->faker->stateAbbr(),
            'location_country' => 'US',
            'location_lat' => $this->faker->latitude(25, 49),
            'location_lng' => $this->faker->longitude(-125, -65),
            'geofence_radius' => 100,
            'early_clockin_minutes' => 15,
            'late_grace_minutes' => 10,

            // Timing
            'shift_date' => $shiftDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'duration_hours' => 8.0,
            'minimum_shift_duration' => 3.0,
            'maximum_shift_duration' => 12.0,
            'required_rest_hours' => 8.0,

            // Pricing (stored as cents)
            'base_rate' => $this->faker->numberBetween(1500, 3500), // $15-35/hr
            'dynamic_rate' => null,
            'final_rate' => null,
            'minimum_wage' => 1500, // $15.00
            'base_worker_pay' => null,
            'platform_fee_rate' => 25.00,
            'platform_fee_amount' => null,
            'vat_rate' => 18.00,
            'vat_amount' => null,
            'total_business_cost' => null,
            'escrow_amount' => null,
            'contingency_buffer_rate' => 5.00,

            // Surge Pricing
            'surge_multiplier' => 1.00,
            'time_surge' => 1.00,
            'demand_surge' => 0.00,
            'event_surge' => 0.00,
            'is_public_holiday' => false,
            'is_night_shift' => false,
            'is_weekend' => false,

            // Status & Lifecycle
            'status' => 'open',
            'urgency_level' => 'normal',
            'requires_overtime_approval' => false,
            'has_disputes' => false,
            'auto_approval_eligible' => true,
            'confirmed_at' => null,
            'priority_notification_sent_at' => null,
            'started_at' => null,
            'first_worker_clocked_in_at' => null,
            'completed_at' => null,
            'last_worker_clocked_out_at' => null,
            'verified_at' => null,
            'verified_by' => null,
            'auto_approved_at' => null,

            // Cancellation
            'cancelled_by' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'cancellation_type' => null,
            'cancellation_penalty_amount' => null,
            'worker_compensation_amount' => null,

            // Worker Capacity
            'required_workers' => 1,
            'filled_workers' => 0,

            // Requirements
            'requirements' => null,
            'required_skills' => null,
            'required_certifications' => null,
            'dress_code' => $this->faker->randomElement(['Business casual', 'Uniform provided', 'Casual', 'Black attire']),
            'parking_info' => $this->faker->randomElement(['Street parking available', 'Free parking lot', 'Paid parking nearby', 'No parking available']),
            'break_info' => '30 minute unpaid break',
            'special_instructions' => null,

            // Live Market
            'in_market' => true,
            'is_demo' => false,
            'market_posted_at' => now(),
            'instant_claim_enabled' => false,
            'market_views' => 0,
            'market_applications' => 0,
            'demo_business_name' => null,

            // Application Tracking
            'application_count' => 0,
            'view_count' => 0,
            'first_application_at' => null,
            'last_application_at' => null,
        ];
    }

    /**
     * Indicate that the shift is filled.
     */
    public function filled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'filled',
                'filled_workers' => $attributes['required_workers'],
            ];
        });
    }

    /**
     * Indicate that the shift is in progress.
     */
    public function inProgress()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'in_progress',
                'started_at' => now()->subHours(2),
                'first_worker_clocked_in_at' => now()->subHours(2),
            ];
        });
    }

    /**
     * Indicate that the shift is completed.
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'started_at' => now()->subDays(1),
                'completed_at' => now()->subDays(1)->addHours(8),
                'first_worker_clocked_in_at' => now()->subDays(1),
                'last_worker_clocked_out_at' => now()->subDays(1)->addHours(8),
            ];
        });
    }

    /**
     * Indicate that the shift has surge pricing.
     */
    public function withSurge()
    {
        return $this->state(function (array $attributes) {
            return [
                'surge_multiplier' => $this->faker->randomFloat(2, 1.2, 2.0),
                'urgency_level' => 'urgent',
            ];
        });
    }
}
