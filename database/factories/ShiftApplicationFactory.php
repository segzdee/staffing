<?php

namespace Database\Factories;

use App\Models\ShiftApplication;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftApplicationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ShiftApplication::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'shift_id' => Shift::factory(),
            'worker_id' => User::factory()->create(['user_type' => 'worker'])->id,
            'status' => 'pending',
            'application_note' => $this->faker->optional()->sentence(),
            'applied_at' => now(),
            'responded_at' => null,
        ];
    }

    /**
     * Indicate that the application was accepted.
     */
    public function accepted()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'accepted',
                'responded_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the application was rejected.
     */
    public function rejected()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'rejected',
                'responded_at' => now(),
            ];
        });
    }
}
