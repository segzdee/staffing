<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QUA-005: Continuous Improvement System
 * Model for tracking votes on improvement suggestions.
 *
 * @property int $id
 * @property int $suggestion_id
 * @property int $user_id
 * @property string $vote_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ImprovementSuggestion $suggestion
 * @property-read \App\Models\User $user
 */
class SuggestionVote extends Model
{
    use HasFactory;

    public const TYPE_UP = 'up';

    public const TYPE_DOWN = 'down';

    protected $fillable = [
        'suggestion_id',
        'user_id',
        'vote_type',
    ];

    /**
     * The suggestion this vote belongs to.
     */
    public function suggestion(): BelongsTo
    {
        return $this->belongsTo(ImprovementSuggestion::class, 'suggestion_id');
    }

    /**
     * The user who cast this vote.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this is an upvote.
     */
    public function isUpvote(): bool
    {
        return $this->vote_type === self::TYPE_UP;
    }

    /**
     * Check if this is a downvote.
     */
    public function isDownvote(): bool
    {
        return $this->vote_type === self::TYPE_DOWN;
    }

    /**
     * Toggle the vote type.
     */
    public function toggle(): void
    {
        $this->vote_type = $this->isUpvote() ? self::TYPE_DOWN : self::TYPE_UP;
        $this->save();
        $this->suggestion->recalculateVotes();
    }

    /**
     * Boot method to handle vote updates.
     */
    protected static function booted(): void
    {
        static::created(function (SuggestionVote $vote) {
            $vote->suggestion->recalculateVotes();
        });

        static::updated(function (SuggestionVote $vote) {
            $vote->suggestion->recalculateVotes();
        });

        static::deleted(function (SuggestionVote $vote) {
            $vote->suggestion->recalculateVotes();
        });
    }
}
