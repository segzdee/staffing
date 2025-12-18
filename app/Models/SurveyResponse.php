<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QUA-003: SurveyResponse Model
 *
 * Stores user responses to surveys including NPS scores and feedback.
 *
 * @property int $id
 * @property int $survey_id
 * @property int $user_id
 * @property int|null $shift_id
 * @property array $answers
 * @property int|null $nps_score
 * @property string|null $feedback_text
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Survey $survey
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Shift|null $shift
 */
class SurveyResponse extends Model
{
    use HasFactory;

    /**
     * NPS Score Categories.
     */
    public const NPS_PROMOTER_MIN = 9;

    public const NPS_PASSIVE_MIN = 7;

    public const NPS_DETRACTOR_MAX = 6;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'survey_id',
        'user_id',
        'shift_id',
        'answers',
        'nps_score',
        'feedback_text',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'answers' => 'array',
        'nps_score' => 'integer',
    ];

    /**
     * Get the survey this response belongs to.
     */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * Get the user who submitted this response.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shift associated with this response (for post-shift surveys).
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Scope for responses with NPS score.
     */
    public function scopeWithNpsScore($query)
    {
        return $query->whereNotNull('nps_score');
    }

    /**
     * Scope for promoters (NPS 9-10).
     */
    public function scopePromoters($query)
    {
        return $query->where('nps_score', '>=', self::NPS_PROMOTER_MIN);
    }

    /**
     * Scope for passives (NPS 7-8).
     */
    public function scopePassives($query)
    {
        return $query->whereBetween('nps_score', [self::NPS_PASSIVE_MIN, self::NPS_PROMOTER_MIN - 1]);
    }

    /**
     * Scope for detractors (NPS 0-6).
     */
    public function scopeDetractors($query)
    {
        return $query->where('nps_score', '<=', self::NPS_DETRACTOR_MAX);
    }

    /**
     * Scope for responses within a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if response is from a promoter.
     */
    public function isPromoter(): bool
    {
        return $this->nps_score !== null && $this->nps_score >= self::NPS_PROMOTER_MIN;
    }

    /**
     * Check if response is from a passive.
     */
    public function isPassive(): bool
    {
        return $this->nps_score !== null
            && $this->nps_score >= self::NPS_PASSIVE_MIN
            && $this->nps_score < self::NPS_PROMOTER_MIN;
    }

    /**
     * Check if response is from a detractor.
     */
    public function isDetractor(): bool
    {
        return $this->nps_score !== null && $this->nps_score <= self::NPS_DETRACTOR_MAX;
    }

    /**
     * Get NPS category label.
     */
    public function getNpsCategory(): ?string
    {
        if ($this->nps_score === null) {
            return null;
        }

        if ($this->isPromoter()) {
            return 'promoter';
        }

        if ($this->isPassive()) {
            return 'passive';
        }

        return 'detractor';
    }

    /**
     * Get answer for a specific question.
     */
    public function getAnswer(string $questionId): mixed
    {
        return $this->answers[$questionId] ?? null;
    }
}
