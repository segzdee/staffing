<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Skill>
 */
class SkillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Bartending', 'Serving', 'Customer Service', 'Cash Handling',
                'Food Preparation', 'Kitchen Work', 'Barista', 'Event Setup',
                'Cleaning', 'Security', 'Forklift Operation', 'Inventory Management',
            ]),
            'industry' => $this->faker->randomElement([
                'hospitality', 'warehousing', 'healthcare', 'retail', 'events', 'administrative',
            ]),
            'category' => $this->faker->randomElement(['Food & Beverage', 'Operations', 'Customer Service']),
            'subcategory' => null,
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
            'requires_certification' => false,
            'required_certification_ids' => null,
            'icon' => null,
            'color' => null,
        ];
    }
}
