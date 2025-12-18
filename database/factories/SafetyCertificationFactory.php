<?php

namespace Database\Factories;

use App\Models\SafetyCertification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SafetyCertification>
 */
class SafetyCertificationFactory extends Factory
{
    protected $model = SafetyCertification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true).' Certification';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement([
                'food_safety',
                'health',
                'security',
                'industry_specific',
                'general',
            ]),
            'issuing_authority' => fake()->company(),
            'validity_months' => fake()->randomElement([12, 24, 36, null]),
            'requires_renewal' => fake()->boolean(80),
            'applicable_industries' => fake()->boolean() ? fake()->randomElements(
                ['hospitality', 'healthcare', 'construction', 'retail', 'events'],
                rand(1, 3)
            ) : null,
            'applicable_positions' => fake()->boolean() ? fake()->randomElements(
                ['cook', 'server', 'bartender', 'security', 'warehouse'],
                rand(1, 3)
            ) : null,
            'is_mandatory' => fake()->boolean(20),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the certification is food safety related.
     */
    public function foodSafety(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'food_safety',
            'applicable_industries' => ['hospitality', 'food_service', 'catering'],
        ]);
    }

    /**
     * Indicate that the certification is health related.
     */
    public function health(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'health',
        ]);
    }

    /**
     * Indicate that the certification is security related.
     */
    public function security(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'security',
        ]);
    }

    /**
     * Indicate that the certification is mandatory.
     */
    public function mandatory(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_mandatory' => true,
        ]);
    }

    /**
     * Indicate that the certification does not expire.
     */
    public function noExpiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'validity_months' => null,
            'requires_renewal' => false,
        ]);
    }

    /**
     * Indicate that the certification is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
