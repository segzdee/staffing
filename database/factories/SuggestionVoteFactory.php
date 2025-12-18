<?php

namespace Database\Factories;

use App\Models\ImprovementSuggestion;
use App\Models\SuggestionVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * QUA-005: Factory for SuggestionVote model.
 */
class SuggestionVoteFactory extends Factory
{
    protected $model = SuggestionVote::class;

    public function definition(): array
    {
        return [
            'suggestion_id' => ImprovementSuggestion::factory(),
            'user_id' => User::factory(),
            'vote_type' => $this->faker->randomElement([
                SuggestionVote::TYPE_UP,
                SuggestionVote::TYPE_DOWN,
            ]),
        ];
    }

    /**
     * Set as upvote.
     */
    public function upvote(): static
    {
        return $this->state(fn (array $attributes) => [
            'vote_type' => SuggestionVote::TYPE_UP,
        ]);
    }

    /**
     * Set as downvote.
     */
    public function downvote(): static
    {
        return $this->state(fn (array $attributes) => [
            'vote_type' => SuggestionVote::TYPE_DOWN,
        ]);
    }
}
