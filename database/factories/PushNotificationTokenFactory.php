<?php

namespace Database\Factories;

use App\Models\PushNotificationToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PushNotificationToken>
 */
class PushNotificationTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PushNotificationToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => fake()->regexify('[a-zA-Z0-9]{152}'),
            'platform' => fake()->randomElement(['fcm', 'apns', 'web']),
            'device_id' => fake()->uuid(),
            'device_name' => fake()->randomElement([
                'iPhone 15 Pro',
                'iPhone 14',
                'Samsung Galaxy S24',
                'Google Pixel 8',
                'OnePlus 12',
            ]),
            'device_model' => fake()->randomElement(['iPhone', 'Android', 'Web']),
            'app_version' => fake()->semver(),
            'is_active' => true,
            'last_used_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the token is for FCM platform.
     */
    public function fcm(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'fcm',
        ]);
    }

    /**
     * Indicate that the token is for APNs platform.
     */
    public function apns(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'apns',
        ]);
    }

    /**
     * Indicate that the token is for Web Push platform.
     */
    public function web(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'web',
        ]);
    }

    /**
     * Indicate that the token is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the token has not been used recently.
     */
    public function stale(int $days = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => now()->subDays($days),
        ]);
    }
}
