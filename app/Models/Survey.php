<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * QUA-003: Survey Model
 *
 * Represents a survey (NPS, CSAT, post-shift, onboarding, or general).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $type
 * @property string $target_audience
 * @property array $questions
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurveyResponse> $responses
 * @property-read int|null $responses_count
 */
class Survey extends Model
{
    use HasFactory;

    /**
     * Survey types.
     */
    public const TYPE_NPS = 'nps';

    public const TYPE_CSAT = 'csat';

    public const TYPE_POST_SHIFT = 'post_shift';

    public const TYPE_ONBOARDING = 'onboarding';

    public const TYPE_GENERAL = 'general';

    /**
     * Target audiences.
     */
    public const AUDIENCE_WORKERS = 'workers';

    public const AUDIENCE_BUSINESSES = 'businesses';

    public const AUDIENCE_ALL = 'all';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'target_audience',
        'questions',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'questions' => 'array',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Get all responses for this survey.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * Scope for active surveys.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for surveys within date range.
     */
    public function scopeWithinDateRange($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('starts_at')
                ->orWhere('starts_at', '<=', now());
        })->where(function ($q) {
            $q->whereNull('ends_at')
                ->orWhere('ends_at', '>=', now());
        });
    }

    /**
     * Scope for surveys by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for surveys targeting specific audience.
     */
    public function scopeForAudience($query, string $audience)
    {
        return $query->where(function ($q) use ($audience) {
            $q->where('target_audience', $audience)
                ->orWhere('target_audience', self::AUDIENCE_ALL);
        });
    }

    /**
     * Check if survey is currently available.
     */
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if survey is NPS type.
     */
    public function isNps(): bool
    {
        return $this->type === self::TYPE_NPS;
    }

    /**
     * Check if survey is post-shift type.
     */
    public function isPostShift(): bool
    {
        return $this->type === self::TYPE_POST_SHIFT;
    }

    /**
     * Get the response count.
     */
    public function getResponseCount(): int
    {
        return $this->responses()->count();
    }

    /**
     * Check if user has responded to this survey.
     */
    public function hasUserResponded(int $userId): bool
    {
        return $this->responses()->where('user_id', $userId)->exists();
    }

    /**
     * Check if user has responded to this survey for a specific shift.
     */
    public function hasUserRespondedForShift(int $userId, int $shiftId): bool
    {
        return $this->responses()
            ->where('user_id', $userId)
            ->where('shift_id', $shiftId)
            ->exists();
    }
}
