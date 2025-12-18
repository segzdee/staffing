<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FIN-015: User Risk Score Model
 *
 * Tracks aggregated risk scores for users.
 *
 * @property int $id
 * @property int $user_id
 * @property int $risk_score
 * @property string $risk_level
 * @property array|null $score_factors
 * @property \Illuminate\Support\Carbon|null $last_calculated_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class UserRiskScore extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'risk_score',
        'risk_level',
        'score_factors',
        'last_calculated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score_factors' => 'array',
            'risk_score' => 'integer',
            'last_calculated_at' => 'datetime',
        ];
    }

    /**
     * Risk level constants.
     */
    public const LEVEL_LOW = 'low';

    public const LEVEL_MEDIUM = 'medium';

    public const LEVEL_HIGH = 'high';

    public const LEVEL_CRITICAL = 'critical';

    /**
     * Risk score thresholds (configurable via config/fraud.php).
     */
    public const THRESHOLD_LOW = 0;

    public const THRESHOLD_MEDIUM = 30;

    public const THRESHOLD_HIGH = 60;

    public const THRESHOLD_CRITICAL = 80;

    /**
     * Get all risk levels.
     *
     * @return array<string, string>
     */
    public static function getRiskLevels(): array
    {
        return [
            self::LEVEL_LOW => 'Low',
            self::LEVEL_MEDIUM => 'Medium',
            self::LEVEL_HIGH => 'High',
            self::LEVEL_CRITICAL => 'Critical',
        ];
    }

    /**
     * Get the user associated with this risk score.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== Scopes ==========

    /**
     * Scope for critical risk users.
     */
    public function scopeCritical($query)
    {
        return $query->where('risk_level', self::LEVEL_CRITICAL);
    }

    /**
     * Scope for high risk users.
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', [self::LEVEL_HIGH, self::LEVEL_CRITICAL]);
    }

    /**
     * Scope for users needing review (medium and above).
     */
    public function scopeNeedsReview($query)
    {
        return $query->whereIn('risk_level', [
            self::LEVEL_MEDIUM,
            self::LEVEL_HIGH,
            self::LEVEL_CRITICAL,
        ]);
    }

    /**
     * Scope by minimum score.
     */
    public function scopeMinScore($query, int $score)
    {
        return $query->where('risk_score', '>=', $score);
    }

    /**
     * Scope for recently calculated.
     */
    public function scopeRecentlyCalculated($query)
    {
        return $query->where('last_calculated_at', '>=', now()->subDay());
    }

    /**
     * Scope for stale scores (not calculated in last 24 hours).
     */
    public function scopeStale($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_calculated_at')
                ->orWhere('last_calculated_at', '<', now()->subDay());
        });
    }

    // ========== Helpers ==========

    /**
     * Determine risk level from score.
     */
    public static function getLevelFromScore(int $score): string
    {
        $thresholds = config('fraud.risk_thresholds', [
            'critical' => 80,
            'high' => 60,
            'medium' => 30,
            'low' => 0,
        ]);

        if ($score >= $thresholds['critical']) {
            return self::LEVEL_CRITICAL;
        }

        if ($score >= $thresholds['high']) {
            return self::LEVEL_HIGH;
        }

        if ($score >= $thresholds['medium']) {
            return self::LEVEL_MEDIUM;
        }

        return self::LEVEL_LOW;
    }

    /**
     * Update the risk score.
     */
    public function updateScore(int $score, array $factors = []): void
    {
        $this->update([
            'risk_score' => min(100, max(0, $score)),
            'risk_level' => self::getLevelFromScore($score),
            'score_factors' => array_merge($this->score_factors ?? [], $factors),
            'last_calculated_at' => now(),
        ]);
    }

    /**
     * Add a factor to the score.
     */
    public function addFactor(string $name, int $points, ?string $reason = null): void
    {
        $factors = $this->score_factors ?? [];
        $factors[] = [
            'name' => $name,
            'points' => $points,
            'reason' => $reason,
            'added_at' => now()->toIso8601String(),
        ];

        $newScore = $this->risk_score + $points;

        $this->update([
            'risk_score' => min(100, max(0, $newScore)),
            'risk_level' => self::getLevelFromScore($newScore),
            'score_factors' => $factors,
            'last_calculated_at' => now(),
        ]);
    }

    /**
     * Get risk level label.
     */
    public function getRiskLevelLabelAttribute(): string
    {
        return self::getRiskLevels()[$this->risk_level] ?? ucfirst($this->risk_level);
    }

    /**
     * Get risk level badge class.
     */
    public function getRiskLevelBadgeClassAttribute(): string
    {
        return match ($this->risk_level) {
            self::LEVEL_CRITICAL => 'bg-red-600 text-white',
            self::LEVEL_HIGH => 'bg-orange-500 text-white',
            self::LEVEL_MEDIUM => 'bg-yellow-500 text-black',
            default => 'bg-green-500 text-white',
        };
    }

    /**
     * Check if user is high risk.
     */
    public function isHighRisk(): bool
    {
        return in_array($this->risk_level, [self::LEVEL_HIGH, self::LEVEL_CRITICAL]);
    }

    /**
     * Check if user is critical risk.
     */
    public function isCritical(): bool
    {
        return $this->risk_level === self::LEVEL_CRITICAL;
    }

    /**
     * Check if score is stale.
     */
    public function isStale(): bool
    {
        if (! $this->last_calculated_at) {
            return true;
        }

        return $this->last_calculated_at->lt(now()->subDay());
    }

    /**
     * Get factor summary.
     */
    public function getFactorSummary(): array
    {
        $factors = $this->score_factors ?? [];
        $summary = [];

        foreach ($factors as $factor) {
            $name = $factor['name'] ?? 'Unknown';
            if (! isset($summary[$name])) {
                $summary[$name] = [
                    'count' => 0,
                    'total_points' => 0,
                ];
            }
            $summary[$name]['count']++;
            $summary[$name]['total_points'] += $factor['points'] ?? 0;
        }

        return $summary;
    }
}
