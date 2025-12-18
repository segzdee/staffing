<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * QUA-003: FeatureRequest Model
 *
 * Stores user-submitted feature requests with voting and status tracking.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $description
 * @property string $category
 * @property string $status
 * @property int $vote_count
 * @property int|null $priority
 * @property string|null $admin_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FeatureRequestVote> $votes
 * @property-read int|null $votes_count
 */
class FeatureRequest extends Model
{
    use HasFactory;

    /**
     * Categories.
     */
    public const CATEGORY_UI = 'ui';

    public const CATEGORY_FEATURE = 'feature';

    public const CATEGORY_INTEGRATION = 'integration';

    public const CATEGORY_MOBILE = 'mobile';

    public const CATEGORY_OTHER = 'other';

    /**
     * Statuses.
     */
    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_PLANNED = 'planned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DECLINED = 'declined';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'status',
        'vote_count',
        'priority',
        'admin_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'vote_count' => 'integer',
        'priority' => 'integer',
    ];

    /**
     * Get the user who submitted this request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all votes for this feature request.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(FeatureRequestVote::class);
    }

    /**
     * Scope for requests by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for requests by category.
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for open requests (not completed or declined).
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_DECLINED]);
    }

    /**
     * Scope to order by popularity (vote count).
     */
    public function scopePopular($query)
    {
        return $query->orderBy('vote_count', 'desc');
    }

    /**
     * Scope for prioritized requests.
     */
    public function scopePrioritized($query)
    {
        return $query->whereNotNull('priority')->orderBy('priority', 'asc');
    }

    /**
     * Check if user has voted for this request.
     */
    public function hasUserVoted(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }

    /**
     * Add a vote from a user.
     */
    public function addVote(int $userId): bool
    {
        if ($this->hasUserVoted($userId)) {
            return false;
        }

        $this->votes()->create(['user_id' => $userId]);
        $this->increment('vote_count');

        return true;
    }

    /**
     * Remove a vote from a user.
     */
    public function removeVote(int $userId): bool
    {
        $vote = $this->votes()->where('user_id', $userId)->first();

        if (! $vote) {
            return false;
        }

        $vote->delete();
        $this->decrement('vote_count');

        return true;
    }

    /**
     * Toggle vote for a user.
     */
    public function toggleVote(int $userId): bool
    {
        if ($this->hasUserVoted($userId)) {
            $this->removeVote($userId);

            return false;
        }

        $this->addVote($userId);

        return true;
    }

    /**
     * Check if request is open for voting.
     */
    public function isOpen(): bool
    {
        return ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_DECLINED]);
    }

    /**
     * Get human-readable category label.
     */
    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_UI => 'User Interface',
            self::CATEGORY_FEATURE => 'New Feature',
            self::CATEGORY_INTEGRATION => 'Integration',
            self::CATEGORY_MOBILE => 'Mobile App',
            self::CATEGORY_OTHER => 'Other',
            default => ucfirst($this->category),
        };
    }

    /**
     * Get human-readable status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_PLANNED => 'Planned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_DECLINED => 'Declined',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_SUBMITTED => 'gray',
            self::STATUS_UNDER_REVIEW => 'blue',
            self::STATUS_PLANNED => 'purple',
            self::STATUS_IN_PROGRESS => 'yellow',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_DECLINED => 'red',
            default => 'gray',
        };
    }
}
