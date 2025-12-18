<?php

namespace Database\Factories;

use App\Models\PushNotificationLog;
use App\Models\PushNotificationToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PushNotificationLog>
 */
class PushNotificationLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PushNotificationLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'sent', 'delivered', 'failed', 'clicked']);

        return [
            'user_id' => User::factory(),
            'token_id' => null,
            'title' => fake()->sentence(3),
            'body' => fake()->sentence(8),
            'data' => [
                'type' => fake()->randomElement(['shift_reminder', 'payment', 'notification']),
                'action_url' => fake()->url(),
            ],
            'platform' => fake()->randomElement(['fcm', 'apns', 'web']),
            'status' => $status,
            'message_id' => $status !== 'pending' ? 'projects/test/messages/'.fake()->uuid() : null,
            'error_message' => $status === 'failed' ? fake()->sentence() : null,
            'sent_at' => in_array($status, ['sent', 'delivered', 'clicked']) ? now() : null,
            'delivered_at' => in_array($status, ['delivered', 'clicked']) ? now() : null,
            'clicked_at' => $status === 'clicked' ? now() : null,
        ];
    }

    /**
     * Indicate that the log is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'message_id' => null,
            'sent_at' => null,
            'delivered_at' => null,
            'clicked_at' => null,
        ]);
    }

    /**
     * Indicate that the notification was sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'message_id' => 'projects/test/messages/'.fake()->uuid(),
            'sent_at' => now(),
            'delivered_at' => null,
            'clicked_at' => null,
        ]);
    }

    /**
     * Indicate that the notification was delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'message_id' => 'projects/test/messages/'.fake()->uuid(),
            'sent_at' => now()->subMinutes(5),
            'delivered_at' => now(),
            'clicked_at' => null,
        ]);
    }

    /**
     * Indicate that the notification failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => fake()->randomElement([
                'Token not registered',
                'Invalid token',
                'Server error',
                'Rate limit exceeded',
            ]),
            'sent_at' => null,
            'delivered_at' => null,
            'clicked_at' => null,
        ]);
    }

    /**
     * Indicate that the notification was clicked.
     */
    public function clicked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'clicked',
            'message_id' => 'projects/test/messages/'.fake()->uuid(),
            'sent_at' => now()->subMinutes(10),
            'delivered_at' => now()->subMinutes(5),
            'clicked_at' => now(),
        ]);
    }

    /**
     * Associate with a specific token.
     */
    public function forToken(PushNotificationToken $token): static
    {
        return $this->state(fn (array $attributes) => [
            'token_id' => $token->id,
            'user_id' => $token->user_id,
            'platform' => $token->platform,
        ]);
    }
}
