<?php

namespace Database\Factories;

use App\Models\CommunicationTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommunicationTemplate>
 */
class CommunicationTemplateFactory extends Factory
{
    protected $model = CommunicationTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->sentence(3);
        $types = array_keys(CommunicationTemplate::getTypes());
        $channels = array_keys(CommunicationTemplate::getChannels());
        $type = fake()->randomElement($types);

        return [
            'business_id' => User::factory()->create(['user_type' => 'business'])->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => $type,
            'channel' => fake()->randomElement($channels),
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(3),
            'variables' => CommunicationTemplate::getDefaultVariablesForType($type),
            'is_default' => false,
            'is_active' => true,
            'is_system' => false,
            'usage_count' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Configure the template as default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Configure the template as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure the template as a system template.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }

    /**
     * Configure the template type.
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'variables' => CommunicationTemplate::getDefaultVariablesForType($type),
        ]);
    }

    /**
     * Configure the template channel.
     */
    public function channel(string $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => $channel,
        ]);
    }

    /**
     * Configure welcome template.
     */
    public function welcome(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'welcome',
            'name' => 'Welcome Message',
            'slug' => 'welcome-message',
            'subject' => 'Welcome to {{business_name}}!',
            'body' => 'Hi {{worker_name}}, welcome to our team!',
            'variables' => CommunicationTemplate::getDefaultVariablesForType('welcome'),
        ]);
    }

    /**
     * Configure shift instruction template.
     */
    public function shiftInstruction(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'shift_instruction',
            'name' => 'Shift Instructions',
            'slug' => 'shift-instructions',
            'subject' => 'Shift Details for {{shift_date}}',
            'body' => 'Hi {{worker_name}}, here are your shift details for {{shift_date}} at {{venue_name}}.',
            'variables' => CommunicationTemplate::getDefaultVariablesForType('shift_instruction'),
        ]);
    }

    /**
     * Configure reminder template.
     */
    public function reminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'reminder',
            'name' => 'Shift Reminder',
            'slug' => 'shift-reminder',
            'subject' => 'Reminder: Shift Tomorrow',
            'body' => 'Hi {{worker_name}}, this is a reminder about your shift tomorrow at {{venue_name}}.',
            'variables' => CommunicationTemplate::getDefaultVariablesForType('reminder'),
        ]);
    }
}
