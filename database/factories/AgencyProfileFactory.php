<?php

namespace Database\Factories;

use App\Models\AgencyProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgencyProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AgencyProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'agency_name' => $this->faker->company() . ' Staffing Agency',
            'license_number' => $this->faker->numerify('AG-######'),
            'license_verified' => false,
            'business_model' => $this->faker->randomElement(['staffing_agency', 'temp_agency', 'consulting']),
            'commission_rate' => $this->faker->randomFloat(2, 10, 25),
            'managed_workers' => [],
            'total_shifts_managed' => 0,
            'total_workers_managed' => 0,
        ];
    }

    /**
     * Indicate that the agency is verified.
     */
    public function verified()
    {
        return $this->state(function (array $attributes) {
            return [
                'license_verified' => true,
            ];
        });
    }

    /**
     * Indicate that the agency has managed workers.
     */
    public function withActivity()
    {
        return $this->state(function (array $attributes) {
            return [
                'total_shifts_managed' => $this->faker->numberBetween(10, 100),
                'total_workers_managed' => $this->faker->numberBetween(5, 50),
            ];
        });
    }
}
