<?php

namespace Database\Factories;

use App\Models\CommunicationTemplate;
use App\Models\TemplateSend;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TemplateSend>
 */
class TemplateSendFactory extends Factory
{
    protected $model = TemplateSend::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['pending', 'sent', 'delivered', 'failed'];

        return [
            'template_id' => CommunicationTemplate::factory(),
            'sender_id' => User::factory()->create(['user_type' => 'business'])->id,
            'recipient_id' => User::factory()->create(['user_type' => 'worker'])->id,
            'shift_id' => null,
            'channel' => fake()->randomElement(['email', 'sms', 'in_app', 'all']),
            'subject' => fake()->sentence(),
            'rendered_content' => fake()->paragraph(2),
            'status' => fake()->randomElement($statuses),
            'error_message' => null,
            'sent_at' => fake()->boolean(70) ? now()->subHours(fake()->numberBetween(1, 48)) : null,
            'delivered_at' => null,
            'read_at' => null,
        ];
    }

    /**
     * Configure the send as pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'sent_at' => null,
            'delivered_at' => null,
        ]);
    }

    /**
     * Configure the send as sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now()->subHours(fake()->numberBetween(1, 24)),
            'delivered_at' => null,
        ]);
    }

    /**
     * Configure the send as delivered.
     */
    public function delivered(): static
    {
        $sentAt = now()->subHours(fake()->numberBetween(1, 24));

        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'sent_at' => $sentAt,
            'delivered_at' => $sentAt->addMinutes(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Configure the send as failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => fake()->sentence(),
            'sent_at' => null,
            'delivered_at' => null,
        ]);
    }

    /**
     * Configure the send as read.
     */
    public function read(): static
    {
        $sentAt = now()->subHours(fake()->numberBetween(1, 24));

        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'sent_at' => $sentAt,
            'delivered_at' => $sentAt->addMinutes(fake()->numberBetween(1, 30)),
            'read_at' => $sentAt->addHours(fake()->numberBetween(1, 12)),
        ]);
    }

    /**
     * Configure the channel.
     */
    public function viaChannel(string $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => $channel,
        ]);
    }
}
